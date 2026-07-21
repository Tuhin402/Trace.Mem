<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_audit_logs', function (Blueprint $table) {
            $table->id();

            // The workspace this audit event belongs to
            $table->foreignId('workspace_id')
                ->constrained('teams')
                ->cascadeOnDelete();

            // The user who performed the action (null = system action)
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Action identifier — dot-notation namespaced
            // Examples: 'workspace.created', 'workspace.renamed', 'workspace.archived',
            //           'workspace.status_changed', 'workspace.switched',
            //           'member.invited', 'member.removed', 'member.role_changed',
            //           'apikey.created', 'apikey.revoked', 'apikey.rotated'
            $table->string('action', 64);

            // Subject of the action (optional)
            $table->string('subject_type', 64)->nullable(); // 'user', 'api_key', 'workspace'
            $table->string('subject_id', 64)->nullable();

            // Request context for traceability
            $table->string('ip_address', 45)->nullable(); // supports IPv6
            $table->string('user_agent')->nullable();

            // Arbitrary JSON payload (event-specific data)
            $table->jsonb('metadata')->nullable();

            // Only created_at — audit logs are immutable
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('workspace_id', 'idx_wal_workspace_id');
            $table->index('created_at', 'idx_wal_created_at');
            $table->index(['workspace_id', 'action'], 'idx_wal_workspace_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_audit_logs');
    }
};
