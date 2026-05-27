<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->text('normalized_content')->nullable()->after('content');
            $table->string('content_hash', 64)->nullable()->after('normalized_content');
            $table->unsignedInteger('access_count')->default(0)->after('last_accessed_at');

            $table->index(['tenant_id', 'user_id', 'content_hash'], 'memories_tenant_user_hash_index');
            $table->index(['tenant_id', 'user_id', 'type'], 'memories_tenant_user_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex('memories_tenant_user_hash_index');
            $table->dropIndex('memories_tenant_user_type_index');
            $table->dropColumn(['normalized_content', 'content_hash', 'access_count']);
        });
    }
};
