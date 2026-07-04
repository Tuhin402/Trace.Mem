<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the free_trial_events table for analytics event tracking.
 *
 * Tracks lifecycle events:
 *   trial_viewed, trial_started, trial_activated, trial_cancelled,
 *   trial_converted, trial_expired, trial_upgraded, trial_downgraded,
 *   trial_reminder_sent
 *
 * Provides admin-visible audit trail + conversion rate analytics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_trial_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Event names: trial_viewed, trial_started, trial_activated,
            // trial_cancelled, trial_converted, trial_expired,
            // trial_upgraded, trial_downgraded, trial_reminder_sent
            $table->string('event_name', 64);

            // Arbitrary JSON context: plan_slug, cycle, days_remaining, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for admin dashboard queries
            $table->index('user_id');
            $table->index('event_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_trial_events');
    }
};
