<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            // workspace_id — nullable on add; backfilled via workspace:backfill command.
            // ON DELETE SET NULL: if workspace is deleted, key orphans (not deleted).
            // workspace_id is IMMUTABLE once set. No update/move endpoint exists.
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('tenant_scope_id')
                ->constrained('teams')
                ->nullOnDelete();

            // Composite index: queries that filter by workspace + environment (most common)
            $table->index(['workspace_id', 'environment'], 'idx_api_keys_workspace_env');

            // Composite index: queries that join tenant_scope_id with workspace
            $table->index(['tenant_scope_id', 'workspace_id'], 'idx_api_keys_tenant_workspace');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex('idx_api_keys_workspace_env');
            $table->dropIndex('idx_api_keys_tenant_workspace');
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
