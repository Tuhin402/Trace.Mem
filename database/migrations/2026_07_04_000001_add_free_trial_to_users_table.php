<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds free trial tracking columns to the users table.
 *
 * All columns are nullable — existing rows are unaffected.
 * Migration is fully reversible via down().
 *
 * free_trial_status state machine:
 *   null                → eligible (never interacted with offer)
 *   'pending_activation'→ checkout in-flight (transient; cleaned up after 15 min)
 *   'activated'         → trial is running, full access granted
 *   'completed'         → first real charge received; offer permanently consumed
 *   'cancelled'         → user cancelled during trial; offer permanently consumed
 *   'upgraded'          → user upgraded to a different plan during trial; offer consumed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // State machine — drives all eligibility and display logic
            $table->string('free_trial_status', 32)->nullable()->default(null)->after('razorpay_customer_id');

            // Timestamps
            $table->timestamp('free_trial_activated_at')->nullable()->after('free_trial_status');
            $table->timestamp('free_trial_ends_at')->nullable()->after('free_trial_activated_at');

            // Reference to which plan the trial was for (audit + email reminders)
            $table->unsignedBigInteger('free_trial_plan_id')->nullable()->after('free_trial_ends_at');
            $table->foreign('free_trial_plan_id')->references('id')->on('subscription_plans')->nullOnDelete();

            // Index for scheduler query: "find users whose trial ends in N days"
            $table->index(['free_trial_status', 'free_trial_ends_at'], 'idx_free_trial_status_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['free_trial_plan_id']);
            $table->dropIndex('idx_free_trial_status_ends_at');
            $table->dropColumn([
                'free_trial_status',
                'free_trial_activated_at',
                'free_trial_ends_at',
                'free_trial_plan_id',
            ]);
        });
    }
};
