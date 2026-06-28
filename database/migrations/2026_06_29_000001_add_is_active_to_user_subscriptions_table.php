<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the is_active boolean column to user_subscriptions.
 *
 * This column is referenced throughout the application
 * (SubscriptionEntitlementService, BillingController, User model, etc.)
 * but was not present in the original create_user_subscriptions_table migration.
 * This migration backfills it safely:
 *   - Existing rows with status = 'active' are set to is_active = true.
 *   - All other existing rows default to is_active = false.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('subscription_plan_id');
        });

        // Backfill: any existing active subscription should be active
        \Illuminate\Support\Facades\DB::table('user_subscriptions')
            ->where('status', 'active')
            ->whereNull('cancelled_at')
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
