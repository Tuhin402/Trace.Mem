<?php

namespace App\Jobs;

use App\Models\BillingTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Auth\SubscriptionCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessStripeWebhookJob — queue: 'high'
 *
 * This job is the sole idempotency authority for Stripe webhook processing.
 * The controller ONLY validates the signature and dispatches this job.
 * All business logic + idempotency lives here.
 *
 * Idempotency strategy: INSERT into stripe_webhook_events with a UNIQUE
 * constraint on event_id. Unique-key violation = duplicate delivery → silent return.
 * This is more reliable than Redis locks for billing-critical work (survives
 * Redis restarts, Redis downtime, worker restarts).
 */
class ProcessStripeWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** Number of retry attempts before job lands in failed_jobs. */
    public int $tries = 5;

    /**
     * Exponential backoff intervals between retries (in seconds).
     * Provides 4 delay values for 5 total attempts.
     */
    public array $backoff = [10, 30, 60, 120];

    /** Maximum execution time in seconds. */
    public int $timeout = 30;

    public function __construct(
        public readonly array $payload,
    ) {
        $this->onQueue('high');
    }

    public function handle(SubscriptionCacheService $subscriptionCache): void
    {
        $eventId   = $this->payload['id']       ?? null;
        $eventType = $this->payload['type']     ?? null;
        $session   = $this->payload['data']['object'] ?? [];

        if (! $eventId || ! $eventType) {
            Log::error('ProcessStripeWebhookJob: missing event_id or event_type', [
                'payload_keys' => array_keys($this->payload),
            ]);
            return;
        }

        // Only process supported event types
        if ($eventType !== 'checkout.session.completed') {
            Log::info('ProcessStripeWebhookJob: unsupported event type, skipping', [
                'event_type' => $eventType,
                'event_id'   => $eventId,
            ]);
            return;
        }

        // ── Step 1–7: Atomic transaction with idempotency guard ────────────
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $session, &$userId) {
            // Step 3: Attempt INSERT — unique violation = duplicate, return silently
            try {
                DB::table('stripe_webhook_events')->insert([
                    'event_id'     => $eventId,
                    'event_type'   => $eventType,
                    'processed_at' => now(),
                    'payload_hash' => hash('sha256', json_encode($session)),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Duplicate delivery — the job already ran for this event
                Log::info('ProcessStripeWebhookJob: duplicate event, skipping', [
                    'event_id' => $eventId,
                ]);
                return; // rollback + return silently
            } catch (\Throwable $e) {
                // SQLite doesn't have UniqueConstraintViolationException in older Laravel
                // Fall back to checking the message
                if (
                    str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                    str_contains($e->getMessage(), 'Duplicate entry') ||
                    str_contains($e->getMessage(), 'unique_violation')
                ) {
                    Log::info('ProcessStripeWebhookJob: duplicate event (fallback check), skipping', [
                        'event_id' => $eventId,
                    ]);
                    return;
                }
                throw $e;
            }

            // Step 4: Extract metadata
            $userId       = (int) data_get($session, 'metadata.user_id');
            $planId       = (int) data_get($session, 'metadata.plan_id');
            $billingCycle = (string) data_get($session, 'metadata.billing_cycle', 'monthly');

            if (! $userId || ! $planId) {
                Log::error('ProcessStripeWebhookJob: missing user_id or plan_id in metadata', [
                    'event_id' => $eventId,
                    'metadata' => data_get($session, 'metadata'),
                ]);
                return;
            }

            // Step 5: Create billing transaction record
            BillingTransaction::create([
                'user_id'                       => $userId,
                'subscription_plan_id'          => $planId,
                'provider'                      => 'stripe',
                'provider_checkout_session_id'  => data_get($session, 'id'),
                'provider_payment_intent_id'    => data_get($session, 'payment_intent'),
                'provider_subscription_id'      => data_get($session, 'subscription'),
                'billing_cycle'                 => $billingCycle,
                'currency'                      => data_get($session, 'currency', 'usd'),
                'amount_total'                  => (int) data_get($session, 'amount_total', 0),
                'status'                        => 'paid',
                'raw_payload'                   => $session,
            ]);

            // Step 6: Create or update the user subscription
            UserSubscription::updateOrCreate(
                [
                    'user_id'              => $userId,
                    'subscription_plan_id' => $planId,
                    'is_active'            => true,
                ],
                [
                    'billing_cycle'    => $billingCycle,
                    'status'           => 'active',
                    'starts_at'        => now(),
                    'renews_at'        => null,
                    'ends_at'          => null,
                    'auto_renew'       => true,
                    'overage_enabled'  => false,
                    'quotas_snapshot'  => [],
                ]
            );

            // Step 7: Update Stripe customer ID on user if provided
            $stripeCustomerId = data_get($session, 'customer');
            if ($stripeCustomerId && ($user = User::find($userId))) {
                $user->forceFill(['stripe_customer_id' => $stripeCustomerId])->save();
            }
        });

        // ── Post-transaction: invalidate caches + dispatch analytics job ───
        // Only if we successfully processed (userId was set by transaction)
        if ($userId && ($user = User::find($userId))) {
            $subscriptionCache->forgetEntitlements($user);
            $subscriptionCache->forgetUserAnalytics($user);

            AggregateUsageStatsJob::dispatch($userId, 'all_time')->onQueue('default');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessStripeWebhookJob: exhausted all retries', [
            'event_id'   => $this->payload['id'] ?? 'unknown',
            'event_type' => $this->payload['type'] ?? 'unknown',
            'error'      => $exception->getMessage(),
        ]);
    }
}
