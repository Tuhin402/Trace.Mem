<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\Memory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackfillTenantsCommand extends Command
{
    protected $signature = 'trace:backfill-tenants 
                            {--chunk=100 : Number of records to process per batch} 
                            {--dry-run : Report what would happen without making database changes}';

    protected $description = 'Safely discovers all legacy tenant_scope_id UUIDs across authoritative tables and backfills the explicit tenants table.';

    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        $isDryRun = $this->option('dry-run');

        $this->info("Starting Tenant Backfill Process.");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN (No changes)' : 'PRODUCTION (Modifying database)'));
        $this->info("Chunk Size: {$chunkSize}");

        // 1. Discover UUIDs via Union
        $this->info("Discovering authoritative tenant UUIDs across platform...");

        $userUuids = User::whereNotNull('tenant_scope_id')->pluck('tenant_scope_id')->toArray();
        $apiKeyUuids = ApiKey::whereNotNull('tenant_scope_id')->pluck('tenant_scope_id')->toArray();
        // Assume memory also stores tenant_scope_id or tenant_id. We grep'd `$memory->tenant_id` earlier.
        $memoryUuids = Memory::whereNotNull('tenant_id')->pluck('tenant_id')->toArray(); 

        $allUuids = array_unique(array_merge($userUuids, $apiKeyUuids, $memoryUuids));

        $this->info("Found " . count($allUuids) . " unique tenant UUIDs across all tables.");

        // 2. Discover NULL Orphans (Reporting only)
        $nullUsers = User::whereNull('tenant_scope_id')->count();
        $nullApiKeys = ApiKey::whereNull('tenant_scope_id')->count();
        $nullMemories = Memory::whereNull('tenant_id')->count();

        if ($nullUsers > 0 || $nullApiKeys > 0 || $nullMemories > 0) {
            $this->warn("ORPHAN WARNING: Found records missing tenant definitions.");
            $this->line("- Users missing tenant_scope_id: {$nullUsers}");
            $this->line("- API Keys missing tenant_scope_id: {$nullApiKeys}");
            $this->line("- Memories missing tenant_id: {$nullMemories}");
            $this->line("Administrator should review these orphans post-deployment.");
        }

        // 3. Process Backfill
        $processed = 0;
        $created = 0;
        $skippedInvalid = 0;
        $errors = 0;

        $chunks = array_chunk($allUuids, $chunkSize);

        foreach ($chunks as $uuidBatch) {
            DB::beginTransaction();
            try {
                foreach ($uuidBatch as $uuid) {
                    $processed++;

                    if (!Str::isUuid($uuid)) {
                        $this->warn("Skipping Invalid UUID Format: {$uuid}");
                        Log::warning("Tenant Backfill: Invalid UUID skipped", ['uuid' => $uuid]);
                        $skippedInvalid++;
                        continue;
                    }

                    if (!$isDryRun) {
                        // Find the authoritative user for naming context
                        $authoritativeUser = User::where('tenant_scope_id', $uuid)->first();
                        
                        $name = $authoritativeUser->company_name ?? $authoritativeUser->name ?? 'Unknown Organization';
                        
                        // Deterministic slug generation preventing collisions
                        $baseSlug = Str::slug($name);
                        $deterministicSlug = $baseSlug . '-' . substr($uuid, 0, 4);

                        Tenant::firstOrCreate(
                            ['id' => $uuid],
                            [
                                'name' => $name,
                                'slug' => $deterministicSlug,
                                'status' => 'active',
                                'plan' => 'Legacy',
                            ]
                        );
                        $created++;
                    }
                }
                
                DB::commit();
                $this->info("Processed batch of " . count($uuidBatch) . "...");
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors++;
                $this->error("Batch failed: " . $e->getMessage());
                Log::error("Tenant Backfill Batch Failed", ['error' => $e]);
            }
        }

        $this->info("-------------------------------------------------");
        $this->info("Backfill Complete.");
        $this->info("Processed: {$processed}");
        $this->info("Created/Verified: {$created}");
        $this->info("Skipped (Invalid UUID): {$skippedInvalid}");
        $this->info("Batch Errors: {$errors}");
        $this->info("-------------------------------------------------");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
