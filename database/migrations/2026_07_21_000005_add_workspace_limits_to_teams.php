<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Workspace-level resource limits (future-proof).
            // NULL = inherit from tenant subscription limits.
            // No enforcement logic yet — structural placeholders only.
            $table->unsignedInteger('max_api_keys')->nullable()->after('features');
            $table->unsignedBigInteger('max_memory_count')->nullable()->after('max_api_keys');
            $table->unsignedBigInteger('max_requests_per_month')->nullable()->after('max_memory_count');
            $table->unsignedBigInteger('max_storage_bytes')->nullable()->after('max_requests_per_month');
            $table->unsignedBigInteger('max_token_usage')->nullable()->after('max_storage_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'max_api_keys',
                'max_memory_count',
                'max_requests_per_month',
                'max_storage_bytes',
                'max_token_usage',
            ]);
        });
    }
};
