<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWorkspacesTenantScopeCommand extends Command
{
    protected $signature = 'trace:backfill-workspaces-tenant';

    protected $description = 'Backfill tenant_scope_id for existing workspaces based on their owner\'s tenant_scope_id.';

    public function handle()
    {
        $this->info("Starting Workspaces Tenant Scope Backfill...");

        $workspaces = Team::whereNull('tenant_scope_id')->get();
        
        $this->info("Found {$workspaces->count()} workspaces missing tenant_scope_id.");

        $processed = 0;
        $skipped = 0;

        foreach ($workspaces as $workspace) {
            // Find the owner
            $owner = $workspace->members()->wherePivot('role', TeamRole::Owner->value)->first();
            
            if ($owner && $owner->tenant_scope_id) {
                $workspace->tenant_scope_id = $owner->tenant_scope_id;
                $workspace->save();
                $processed++;
            } else {
                $this->warn("Workspace ID {$workspace->id} ({$workspace->name}) has no owner or owner lacks tenant_scope_id.");
                $skipped++;
            }
        }

        $this->info("-------------------------------------------------");
        $this->info("Backfill Complete.");
        $this->info("Processed/Updated: {$processed}");
        $this->info("Skipped (No Owner): {$skipped}");
        $this->info("-------------------------------------------------");

        return Command::SUCCESS;
    }
}
