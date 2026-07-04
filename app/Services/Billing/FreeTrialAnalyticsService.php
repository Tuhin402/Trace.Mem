<?php

namespace App\Services\Billing;

use App\Models\FreeTrialEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * FreeTrialAnalyticsService — records lifecycle events for the Founding Offer.
 *
 * Supported events:
 *   trial_viewed        — user saw the offer (pricing/billing page)
 *   trial_started       — user initiated checkout for a trial subscription
 *   trial_activated     — authentication confirmed; trial is live and access granted
 *   trial_cancelled     — user cancelled during the trial period
 *   trial_converted     — first real billing charge captured (trial → paid)
 *   trial_expired       — trial ended without a billing webhook (edge case)
 *   trial_upgraded      — user subscribed to a different plan during trial
 *   trial_downgraded    — user downgraded during trial
 *   trial_reminder_sent — a reminder email was dispatched (metadata: days_remaining)
 *
 * All writes are fire-and-forget — failures are logged but never thrown.
 */
class FreeTrialAnalyticsService
{
    /**
     * Record a free trial analytics event.
     *
     * @param  User   $user
     * @param  string $eventName  One of the documented event names above.
     * @param  array  $metadata   Optional context (plan_slug, cycle, days_remaining, etc.)
     */
    public function track(User $user, string $eventName, array $metadata = []): void
    {
        try {
            FreeTrialEvent::create([
                'user_id'    => $user->id,
                'event_name' => $eventName,
                'metadata'   => $metadata ?: null,
            ]);

            Log::info("FreeTrialAnalytics: {$eventName}", array_merge([
                'user_id' => $user->id,
            ], $metadata));
        } catch (\Throwable $e) {
            // Analytics failure must never break the main flow.
            Log::error("FreeTrialAnalytics: failed to record [{$eventName}]", [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
