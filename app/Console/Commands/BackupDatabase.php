<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * BackupDatabase
 *
 * Creates a timestamped, compressed backup of the application database.
 *
 * Supported drivers:
 *   - sqlite    → copies the .sqlite file directly
 *   - pgsql     → runs pg_dump and pipes through gzip
 *   - mysql     → runs mysqldump and pipes through gzip
 *
 * Managed databases (RDS, Supabase, PlanetScale, Neon, etc.):
 *   If you cannot run pg_dump/mysqldump against your database from the web
 *   server, set BACKUP_DRIVER=managed in .env. The command will log a
 *   reminder to use the provider's native backup tool and exit cleanly.
 *
 * Environment variables:
 *   BACKUP_DIR               Storage path for backups (default: storage/backups)
 *   BACKUP_RETENTION_DAYS    Days to keep old backups (default: 30)
 *   BACKUP_DRIVER            Override DB driver detection (sqlite/pgsql/mysql/managed)
 *
 * Database connection env vars used:
 *   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   (read from config('database.connections.*') — never from env() directly)
 *
 * Scheduler:
 *   Registered in routes/console.php — runs daily at 02:00 via cron → schedule:run.
 *   NOT queue-based. Uses withoutOverlapping() to prevent concurrent runs.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--dir= : Override backup directory (default: BACKUP_DIR env or storage/backups)}
        {--retention= : Days to retain backups (default: BACKUP_RETENTION_DAYS env or 30)}
        {--driver= : Force database driver (sqlite|pgsql|mysql|managed)}';

    protected $description = 'Create a compressed, timestamped database backup and prune old backups';

    public function handle(): int
    {
        $startedAt = microtime(true);

        $driver  = $this->option('driver')
            ?? env('BACKUP_DRIVER')
            ?? config('database.default', 'sqlite');

        $backupDir = rtrim(
            $this->option('dir') ?? env('BACKUP_DIR', storage_path('backups')),
            DIRECTORY_SEPARATOR
        );

        $retentionDays = (int) ($this->option('retention') ?? env('BACKUP_RETENTION_DAYS', 30));

        // ── Managed database guard ────────────────────────────────────────────
        if ($driver === 'managed') {
            $this->warn('BACKUP_DRIVER=managed: skipping local backup.');
            $this->line('Use your database provider\'s native backup tool (e.g., RDS automated backups, Supabase dashboard, PlanetScale branching).');
            Log::info('[backup:database] Skipped — managed database driver. Use provider backup tools.');
            return self::SUCCESS;
        }

        // ── Prepare backup directory ──────────────────────────────────────────
        if (! is_dir($backupDir) && ! mkdir($backupDir, 0750, true)) {
            $this->error("Cannot create backup directory: {$backupDir}");
            Log::error('[backup:database] Failed to create backup directory.', ['dir' => $backupDir]);
            return self::FAILURE;
        }

        $timestamp  = now()->format('Y_m_d_H_i_s');
        $backupFile = "{$backupDir}/backup_{$timestamp}";

        // ── Run the backup ────────────────────────────────────────────────────
        $this->info("Starting {$driver} backup…");

        try {
            $finalPath = match ($driver) {
                'sqlite' => $this->backupSqlite($backupFile),
                'pgsql'  => $this->backupPostgres($backupFile),
                'mysql'  => $this->backupMysql($backupFile),
                default  => throw new \RuntimeException("Unsupported DB driver: {$driver}"),
            };
        } catch (\RuntimeException $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('[backup:database] Backup failed.', [
                'driver' => $driver,
                'error'  => $e->getMessage(),
            ]);
            return self::FAILURE;
        }

        $duration = round(microtime(true) - $startedAt, 2);
        $sizeBytes = file_exists($finalPath) ? filesize($finalPath) : 0;
        $sizeHuman = $this->humanFileSize($sizeBytes);

        $this->info("Backup saved: {$finalPath}");
        $this->line("  Duration : {$duration}s");
        $this->line("  Size     : {$sizeHuman}");

        Log::info('[backup:database] Backup completed successfully.', [
            'driver'       => $driver,
            'file'         => $finalPath,
            'size_bytes'   => $sizeBytes,
            'duration_sec' => $duration,
        ]);

        // ── Prune old backups ─────────────────────────────────────────────────
        $this->pruneOldBackups($backupDir, $retentionDays);

        return self::SUCCESS;
    }

    // ── Driver implementations ────────────────────────────────────────────────

    /**
     * SQLite: copy the database file and gzip it.
     */
    private function backupSqlite(string $backupBase): string
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! $dbPath || ! file_exists($dbPath)) {
            throw new \RuntimeException("SQLite database file not found: {$dbPath}");
        }

        $dest = "{$backupBase}.sqlite.gz";

        // Stream through gzip to avoid doubling memory usage.
        $source = fopen($dbPath, 'rb');
        $gz     = gzopen($dest, 'wb9');

        if (! $source || ! $gz) {
            throw new \RuntimeException("Failed to open file handles for SQLite backup.");
        }

        while (! feof($source)) {
            gzwrite($gz, fread($source, 65536));
        }

        fclose($source);
        gzclose($gz);

        return $dest;
    }

    /**
     * PostgreSQL: pg_dump → gzip.
     * Requires pg_dump on the server's PATH.
     */
    private function backupPostgres(string $backupBase): string
    {
        $conn = config('database.connections.pgsql');
        $dest = "{$backupBase}.pgsql.gz";

        $host     = escapeshellarg($conn['host'] ?? '127.0.0.1');
        $port     = (int) ($conn['port'] ?? 5432);
        $database = escapeshellarg($conn['database'] ?? '');
        $username = escapeshellarg($conn['username'] ?? '');
        $password = $conn['password'] ?? '';
        $destArg  = escapeshellarg($dest);

        // Set PGPASSWORD in the process environment (never written to disk or logs).
        $env     = ! empty($password) ? "PGPASSWORD=" . escapeshellarg($password) . " " : '';
        $command = "{$env}pg_dump --host={$host} --port={$port} --username={$username} --no-password --format=plain {$database} | gzip > {$destArg} 2>&1";

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException("pg_dump exited with code {$exitCode}. Output: " . implode(' ', $output));
        }

        return $dest;
    }

    /**
     * MySQL / MariaDB: mysqldump → gzip.
     * Requires mysqldump on the server's PATH.
     */
    private function backupMysql(string $backupBase): string
    {
        $conn = config('database.connections.mysql');
        $dest = "{$backupBase}.sql.gz";

        $host     = escapeshellarg($conn['host'] ?? '127.0.0.1');
        $port     = (int) ($conn['port'] ?? 3306);
        $database = escapeshellarg($conn['database'] ?? '');
        $username = escapeshellarg($conn['username'] ?? '');
        $password = $conn['password'] ?? '';
        $destArg  = escapeshellarg($dest);

        $passArg = ! empty($password) ? "-p" . escapeshellarg($password) : '';
        $command = "mysqldump --host={$host} --port={$port} --user={$username} {$passArg} --single-transaction --routines --triggers {$database} | gzip > {$destArg} 2>&1";

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException("mysqldump exited with code {$exitCode}. Output: " . implode(' ', $output));
        }

        return $dest;
    }

    // ── Pruning ───────────────────────────────────────────────────────────────

    /**
     * Delete backup files older than $retentionDays.
     */
    private function pruneOldBackups(string $backupDir, int $retentionDays): void
    {
        $cutoff  = time() - ($retentionDays * 86400);
        $pattern = $backupDir . '/backup_*.{sqlite.gz,pgsql.gz,sql.gz}';
        $files   = glob($pattern, GLOB_BRACE) ?: [];
        $pruned  = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (@unlink($file)) {
                    $pruned++;
                    $this->line("  Pruned: " . basename($file));
                }
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup(s) older than {$retentionDays} days.");
            Log::info("[backup:database] Pruned {$pruned} old backup(s).", [
                'retention_days' => $retentionDays,
                'backup_dir'     => $backupDir,
            ]);
        } else {
            $this->line("  No old backups to prune.");
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}
