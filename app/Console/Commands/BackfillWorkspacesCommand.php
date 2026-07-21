<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\ApiKey;
use App\Models\Memory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillWorkspacesCommand extends Command
{
    protected $signature = 'workspace:backfill
                            {--chunk=100 : Number of records to process per batch}
                            {--dry-run   : Preview what would be changed without writing}';

    protected $description = 'Backfill workspace_id on api_keys and memories. Creates default workspaces for users who have none.';

    public function handle(): int
    {
        $chunk  = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $this->info('=== workspace:backfill ===');
        $this->newLine();

        $this->step1_createDefaultWorkspaces($chunk, $dryRun);
        $this->step2_backfillApiKeys($chunk, $dryRun);
        $this->step3_backfillMemories($chunk, $dryRun);

        $this->newLine();
        $this->info('=== Backfill complete ===');

        $this->printVerification();

        return self::SUCCESS;
    }

    // =========================================================================
    // Step 1 — Create default workspaces for users with no team memberships
    //
    // NOTE: Uses raw DB queries instead of User::teams() relationship because
    //       the HasTeams concern is added in Phase B. This command must be
    //       self-contained and runnable from Phase A onwards.
    // =========================================================================

    private function step1_createDefaultWorkspaces(int $chunk, bool $dryRun): void
    {
        $this->info('Step 1: Creating default workspaces for users with no team memberships...');

        // Find users who have zero rows in team_members — no model relationship needed
        $usersWithoutTeams = User::whereNotIn(
            'id',
            DB::table('team_members')->select('user_id')
        )->cursor();

        $created = 0;

        foreach ($usersWithoutTeams as $user) {
            $this->line("  → User #{$user->id} ({$user->email}) has no workspace");

            if ($dryRun) {
                $created++;
                continue;
            }

            try {
                DB::transaction(function () use ($user) {
                    $workspace = Team::create([
                        'name'        => 'Default',
                        'is_personal' => $user->account_type === 'individual',
                        'status'      => 'active',
                        'settings'    => null,
                        'features'    => null,
                    ]);

                    DB::table('team_members')->insert([
                        'team_id'    => $workspace->id,
                        'user_id'    => $user->id,
                        'role'       => TeamRole::Owner->value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Set current_team_id if not already set
                    if ($user->current_team_id === null) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['current_team_id' => $workspace->id]);
                    }
                });

                $created++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed for user #{$user->id}: {$e->getMessage()}");
                Log::error('workspace:backfill step1 failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->info("  ✓ {$created} default workspaces " . ($dryRun ? 'would be' : 'were') . " created.");
        $this->newLine();
    }

    // =========================================================================
    // Step 2 — Backfill workspace_id on api_keys
    //
    // For each key: find the user's personal/default team from DB directly.
    // workspace_id is IMMUTABLE once set — this only runs on NULL rows.
    // =========================================================================

    private function step2_backfillApiKeys(int $chunk, bool $dryRun): void
    {
        $this->info('Step 2: Backfilling workspace_id on api_keys...');

        $total   = 0;
        $skipped = 0;

        ApiKey::whereNull('workspace_id')
            ->chunkById($chunk, function ($keys) use ($dryRun, &$total, &$skipped) {
                foreach ($keys as $key) {
                    // Find user's default workspace via raw DB — no model relationship
                    $workspace = $this->resolveDefaultWorkspace($key->user_id);

                    if (!$workspace) {
                        $this->warn("  ⚠ ApiKey #{$key->id} (user_id={$key->user_id}) — no active workspace found, skipping");
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  → ApiKey #{$key->id} would get workspace_id={$workspace->id} ('{$workspace->name}')");
                        $total++;
                        continue;
                    }

                    try {
                        DB::table('api_keys')
                            ->where('id', $key->id)
                            ->update(['workspace_id' => $workspace->id]);
                        $total++;
                    } catch (\Throwable $e) {
                        $this->error("  ✗ ApiKey #{$key->id}: {$e->getMessage()}");
                        Log::error('workspace:backfill step2 failed', [
                            'api_key_id' => $key->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("  ✓ {$total} api_keys " . ($dryRun ? 'would be' : 'were') . " backfilled. {$skipped} skipped.");
        $this->newLine();
    }

    // =========================================================================
    // Step 3 — Backfill workspace_id on memories
    //
    // Match memory.tenant_id → users.tenant_scope_id to find the owner,
    // then assign their default workspace.
    // =========================================================================

    private function step3_backfillMemories(int $chunk, bool $dryRun): void
    {
        $this->info('Step 3: Backfilling workspace_id on memories...');

        $total   = 0;
        $skipped = 0;

        Memory::whereNull('workspace_id')
            ->chunkById($chunk, function ($memories) use ($dryRun, &$total, &$skipped) {
                foreach ($memories as $memory) {
                    // Match tenant_id → user.tenant_scope_id via raw DB
                    $user = DB::table('users')
                        ->where('tenant_scope_id', $memory->tenant_id)
                        ->select('id')
                        ->first();

                    if (!$user) {
                        $this->warn("  ⚠ Memory #{$memory->id} tenant_id={$memory->tenant_id} — no matching user, skipping");
                        $skipped++;
                        continue;
                    }

                    $workspace = $this->resolveDefaultWorkspace($user->id);

                    if (!$workspace) {
                        $this->warn("  ⚠ Memory #{$memory->id} user_id={$user->id} — no active workspace found, skipping");
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  → Memory #{$memory->id} would get workspace_id={$workspace->id} ('{$workspace->name}')");
                        $total++;
                        continue;
                    }

                    try {
                        DB::table('memories')
                            ->where('id', $memory->id)
                            ->update(['workspace_id' => $workspace->id]);
                        $total++;
                    } catch (\Throwable $e) {
                        $this->error("  ✗ Memory #{$memory->id}: {$e->getMessage()}");
                        Log::error('workspace:backfill step3 failed', [
                            'memory_id' => $memory->id,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("  ✓ {$total} memories " . ($dryRun ? 'would be' : 'were') . " backfilled. {$skipped} skipped.");
        $this->newLine();
    }

    // =========================================================================
    // Internal — resolve the default workspace for a given user_id via raw DB.
    // Prefers the personal (is_personal=true) team. Falls back to any active team.
    // Returns null if the user has no teams at all.
    // =========================================================================

    private function resolveDefaultWorkspace(int $userId): ?object
    {
        return DB::table('teams')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $userId)
            ->where('teams.status', 'active')
            ->whereNull('teams.deleted_at')
            ->select('teams.id', 'teams.name', 'teams.is_personal')
            ->orderByDesc('teams.is_personal') // personal workspace first
            ->first();
    }

    // =========================================================================
    // Verification summary
    // =========================================================================

    private function printVerification(): void
    {
        $this->info('Verification:');

        $nullApiKeys  = DB::table('api_keys')->whereNull('workspace_id')->count();
        $nullMemories = DB::table('memories')->whereNull('workspace_id')->count();
        $noTeamUsers  = User::whereNotIn('id', DB::table('team_members')->select('user_id'))->count();

        $this->line("  api_keys  WHERE workspace_id IS NULL → {$nullApiKeys}");
        $this->line("  memories  WHERE workspace_id IS NULL → {$nullMemories}");
        $this->line("  users     with no team memberships   → {$noTeamUsers}");

        if ($nullApiKeys === 0 && $nullMemories === 0 && $noTeamUsers === 0) {
            $this->info('  ✓ All records backfilled successfully.');
        } else {
            $this->warn('  ⚠ Some records remain unbackfilled. Re-run or investigate skipped records in the log.');
        }
    }
}
