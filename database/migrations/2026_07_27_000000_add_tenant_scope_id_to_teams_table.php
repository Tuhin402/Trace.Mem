<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->uuid('tenant_scope_id')->nullable()->after('id');
            // Drop the old global unique slug
            $table->dropUnique(['slug']);
            // Add the new tenant-scoped unique slug
            $table->unique(['tenant_scope_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['tenant_scope_id', 'slug']);
            $table->unique(['slug']);
            $table->dropColumn('tenant_scope_id');
        });
    }
};
