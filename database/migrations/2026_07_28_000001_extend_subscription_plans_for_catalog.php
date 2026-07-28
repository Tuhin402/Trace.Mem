<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'archived'])->default('active')->after('description');
            $table->enum('visibility', ['public', 'private'])->default('public')->after('status');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('visibility');
            $table->json('metadata')->nullable()->after('is_active');
            $table->text('notes')->nullable()->after('metadata');
            $table->timestamp('archived_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'visibility',
                'sort_order',
                'metadata',
                'notes',
                'archived_at'
            ]);
        });
    }
};
