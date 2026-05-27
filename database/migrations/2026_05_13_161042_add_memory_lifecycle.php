<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('access_count');
            $table->timestamp('last_reinforced_at')->nullable()->after('last_accessed_at');
            $table->timestamp('archived_at')->nullable()->after('last_reinforced_at');

            $table->index(['tenant_id', 'user_id', 'status'], 'memories_tenant_user_status_index');
            $table->index(['tenant_id', 'user_id', 'archived_at'], 'memories_tenant_user_archived_index');
        });

        DB::table('memories')->whereNull('status')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex('memories_tenant_user_status_index');
            $table->dropIndex('memories_tenant_user_archived_index');

            $table->dropColumn([
                'status',
                'last_reinforced_at',
                'archived_at',
            ]);
        });
    }
};
