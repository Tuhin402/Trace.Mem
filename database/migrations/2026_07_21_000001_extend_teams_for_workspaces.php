<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Workspace lifecycle status
            // 'active' | 'archived' | 'suspended'
            // Default (is_personal=true) workspace can NEVER be archived or suspended.
            $table->string('status', 16)->default('active')->after('is_personal');

            // Human-readable workspace purpose
            $table->string('purpose')->nullable()->after('status');

            // Deployment environment label
            // 'development' | 'staging' | 'production' | 'testing'
            $table->string('environment', 32)->nullable()->after('purpose');

            // Workspace settings (jsonb) — timezone, default_memory_mode, retention_days, etc.
            $table->jsonb('settings')->nullable()->after('environment');

            // Feature flags (jsonb) — semantic_memory, ai_first, audit_logs, etc.
            $table->jsonb('features')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['status', 'purpose', 'environment', 'settings', 'features']);
        });
    }
};
