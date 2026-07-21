<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            // workspace_id — nullable on add; backfilled via workspace:backfill command.
            // ON DELETE SET NULL: if workspace deleted, memory orphans (not deleted).
            // workspace_id is IMMUTABLE once set. No move/transfer endpoint exists.
            // tenant_id column is NEVER removed — backward compat for all existing queries.
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('user_id')
                ->constrained('teams')
                ->nullOnDelete();

            // Composite index: the primary query pattern (tenant + workspace isolation)
            $table->index(['tenant_id', 'workspace_id'], 'idx_memories_tenant_workspace');

            // Composite index: workspace + user queries
            $table->index(['workspace_id', 'user_id'], 'idx_memories_workspace_user');
        });
    }

    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex('idx_memories_tenant_workspace');
            $table->dropIndex('idx_memories_workspace_user');
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
