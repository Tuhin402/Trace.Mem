<?php

namespace App\Jobs;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\BillingTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Auth\SubscriptionCacheService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ProcessRazorpayWebhookJob — queue: 'high'
 *
 * This job is the sole idempotency authority for Razorpay webhook processing.
 * The controller ONLY validates the signature and dispatches this job.
 * All business logic + idempotency lives here.
 *
 * Idempotency strategy: INSERT into razorpay_webhook_events with a UNIQUE
 * constraint on event_id (= HMAC signature from the controller).
 * Identical re-deliveries produce the same signature → same INSERT → UNIQUE
 * constraint violation → duplicate delivery detected → silent return.
 *
 * Supported Razorpay webhook events:
 *   - subscription.activated
 *   - subscription.charged
 *   - subscription.completed
 *   - subscription.cancelled
 *   - subscription.paused
 *   - subscription.resumed
 *   - payment.captured
 *   - payment.failed
 *   - refund.processed
 *   - order.paid (optional; supplemental logging)
 */
class ProcessRazorpayWebhookJob implements ShouldQueue
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
        public readonly array  $payload,
        public readonly string $eventId,   // HMAC signature — idempotency key
    ) {
        $this->onQueue('high');
    }

    /* ═══════════════════════════════════════════════════════════════
     *  ENTRY POINT
     * ═══════════════════════════════════════════════════════════════ */

    public function handle(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = $this->payload['event'] ?? null;

        if (! $eventType) {
            Log::error('ProcessRazorpayWebhookJob: missing event type in payload', [
                'payload_keys' => array_keys($this->payload),
            ]);
            return;
        }

        match ($eventType) {
            'subscription.activated' => $this->handleSubscriptionActivated($subscriptionCache),
            'subscription.charged'   => $this->handleSubscriptionCharged($subscriptionCache),
            'subscription.completed' => $this->handleSubscriptionCompleted($subscriptionCache),
            'subscription.cancelled' => $this->handleSubscriptionCancelled($subscriptionCache),
            'subscription.paused'    => $this->handleSubscriptionPaused($subscriptionCache),
            'subscription.resumed'   => $this->handleSubscriptionResumed($subscriptionCache),
            'payment.captured'       => $this->handlePaymentCaptured($subscriptionCache),
            'payment.failed'         => $this->handlePaymentFailed($subscriptionCache),
            'refund.processed'       => $this->handleRefundProcessed(),
            'order.paid'             => $this->handleOrderPaid(),
            default                  => $this->handleUnsupported($eventType),
        };
    }

    /* ═══════════════════════════════════════════════════════════════
     *  IDEMPOTENCY GUARD (reused by every handler)
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Attempt to INSERT the event into razorpay_webhook_events.
     * Returns false if already processed (duplicate delivery) → caller should bail out.
     * Must be called at the top of every handler's DB::transaction.
     *
     * @throws \Throwable — re-throws non-duplicate DB errors
     */
    private function recordOrSkip(string $eventType): bool
    {
        try {
            DB::table('razorpay_webhook_events')->insert([
                'event_id'     => $this->eventId,
                'event_type'   => $eventType,
                'processed_at' => now(),
                'payload_hash' => hash('sha256', json_encode($this->payload)),
            ]);
            return true;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            Log::info('ProcessRazorpayWebhookJob: duplicate event — skipping', [
                'event_type' => $eventType,
                'event_id'   => substr($this->eventId, 0, 32) . '…',
            ]);
            return false;
        } catch (\Throwable $e) {
            // SQLite / older driver fallback for UNIQUE violations
            if (
                str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'unique_violation')
            ) {
                Log::info('ProcessRazorpayWebhookJob: duplicate event (fallback check) — skipping', [
                    'event_type' => $eventType,
                    'event_id'   => substr($this->eventId, 0, 32) . '…',
                ]);
                return false;
            }
            throw $e;
        }
    }

    /**
     * Invalidate all subscription-related caches and re-aggregate analytics.
     */
    private function invalidateCaches(User $user, SubscriptionCacheService $subscriptionCache): void
    {
        $subscriptionCache->forgetEntitlements($user);
        $subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');
    }

    /**
     * Look up a user by their internal ID embedded in Razorpay subscription notes.
     */
    private function findUserFromNotes(array $entity, string $eventType): ?User
    {
        $userId = (int) data_get($entity, 'notes.user_id');

        if (! $userId) {
            Log::warning("ProcessRazorpayWebhookJob [{$eventType}]: no user_id in subscription notes", [
                'event_id' => substr($this->eventId, 0, 32) . '…',
            ]);
            return null;
        }

        $user = User::find($userId);

        if (! $user) {
            Log::warning("ProcessRazorpayWebhookJob [{$eventType}]: user not found", [
                'user_id'  => $userId,
                'event_id' => substr($this->eventId, 0, 32) . '…',
            ]);
        }

        return $user;
    }

    /**
     * Extract the subscription entity from payload (for subscription.* events).
     */
    private function subscriptionEntity(): array
    {
        return data_get($this->payload, 'payload.subscription.entity', []);
    }

    /**
     * Extract the payment entity from payload (for payment.* and subscription.charged events).
     */
    private function paymentEntity(): array
    {
        return data_get($this->payload, 'payload.payment.entity', []);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.activated
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Razorpay has activated the subscription (first authentication completed).
     * Ensures the UserSubscription is active and caches are refreshed.
     *
     * Payload: payload.subscription.entity
     *   id            — Razorpay subscription ID (sub_xxx)
     *   status        — 'active'
     *   current_start — Unix timestamp (start of current billing period)
     *   current_end   — Unix timestamp (end of current billing period → renews_at)
     *   customer_id   — Razorpay customer ID
     *   notes.user_id — our internal user ID
     *   notes.plan_id — our internal plan ID
     *   notes.billing_cycle
     */
    private function handleSubscriptionActivated(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType  = 'subscription.activated';
        $userId     = null;
        $currentEnd = null;

        DB::transaction(function () use ($eventType, &$userId, &$currentEnd) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub         = $this->subscriptionEntity();
            $rzpSubId    = data_get($sub, 'id');
            $currentEnd  = data_get($sub, 'current_end');
            $customerId  = data_get($sub, 'customer_id');

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId  = $user->id;
            $planId  = (int) data_get($sub, 'notes.plan_id');
            $cycle   = (string) data_get($sub, 'notes.billing_cycle', 'monthly');

            // Activate the subscription — idempotent; safe if verifyPayment already ran
            $userSub = UserSubscription::updateOrCreate(
                [
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $planId,
                    'is_active'            => true,
                ],
                [
                    'billing_cycle'   => $cycle,
                    'status'          => 'active',
                    'starts_at'       => now(),
                    'renews_at'       => $currentEnd ? Carbon::createFromTimestamp($currentEnd) : null,
                    'ends_at'         => null,
                    'auto_renew'      => true,
                    'overage_enabled' => false,
                    'quotas_snapshot' => [],
                ]
            );

            // Link the pending BillingTransaction to the activated subscription
            if ($rzpSubId) {
                BillingTransaction::where('provider_subscription_id', $rzpSubId)
                    ->where('user_id', $user->id)
                    ->whereNull('user_subscription_id')
                    ->update(['user_subscription_id' => $userSub->id]);
            }

            // Persist Razorpay customer ID on user
            if ($customerId && ! $user->razorpay_customer_id) {
                $user->forceFill(['razorpay_customer_id' => $customerId])->save();
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.activated]: subscription activated', [
                'user_id'    => $user->id,
                'rzp_sub_id' => $rzpSubId,
                'renews_at'  => $currentEnd,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);

            // Dispatch subscription purchased notification email
            $sub = $this->subscriptionEntity();
            $planId = (int) data_get($sub, 'notes.plan_id');
            $cycle  = (string) data_get($sub, 'notes.billing_cycle', 'monthly');
            $plan   = \App\Models\SubscriptionPlan::find($planId);

            SendEmailJob::dispatch(
                template:       EmailTemplate::SubscriptionPurchased,
                data:           [
                    'user_name'     => $user->name,
                    'plan_name'     => $plan?->name ?? 'Trace.Mem Plan',
                    'billing_cycle' => $cycle,
                    'amount'        => 'See billing page',
                    'starts_at'     => now()->format('M j, Y'),
                    'renews_at'     => $currentEnd ? Carbon::createFromTimestamp($currentEnd)->format('M j, Y') : null,
                    'dashboard_url' => url('/dashboard'),
                    'billing_url'   => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            )->onQueue('emails');
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.charged
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Razorpay has successfully charged the subscription (renewal payment).
     * Extends renews_at, records a BillingTransaction, refreshes caches.
     *
     * Payload: payload.subscription.entity + payload.payment.entity
     */
    private function handleSubscriptionCharged(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType  = 'subscription.charged';
        $userId     = null;
        $currentEnd = null;

        DB::transaction(function () use ($eventType, &$userId, &$currentEnd) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub        = $this->subscriptionEntity();
            $payment    = $this->paymentEntity();
            $rzpSubId   = data_get($sub, 'id');
            $currentEnd = data_get($sub, 'current_end');
            $paymentId  = data_get($payment, 'id');
            $amountPaid = (int) data_get($payment, 'amount', 0);
            $currency   = (string) data_get($payment, 'currency', 'INR');

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId = $user->id;
            $planId = (int) data_get($sub, 'notes.plan_id');
            $cycle  = (string) data_get($sub, 'notes.billing_cycle', 'monthly');

            // Advance renews_at on the active subscription
            $userSub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub) {
                $userSub->update([
                    'status'    => 'active',
                    'renews_at' => $currentEnd ? Carbon::createFromTimestamp($currentEnd) : $userSub->renews_at,
                    'auto_renew' => true,
                ]);
            }

            // Record the renewal transaction (skip if payment already recorded)
            $alreadyRecorded = BillingTransaction::where('provider_payment_intent_id', $paymentId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $alreadyRecorded && $paymentId) {
                BillingTransaction::create([
                    'user_id'                    => $user->id,
                    'subscription_plan_id'       => $planId ?: ($userSub?->subscription_plan_id ?? 0),
                    'user_subscription_id'       => $userSub?->id,
                    'provider'                   => 'razorpay',
                    'provider_payment_intent_id' => $paymentId,
                    'provider_subscription_id'   => $rzpSubId,
                    'billing_cycle'              => $cycle ?: ($userSub?->billing_cycle ?? 'monthly'),
                    'currency'                   => $currency,
                    'amount_total'               => $amountPaid,
                    'status'                     => 'paid',
                    'raw_payload'                => $this->payload,
                ]);
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.charged]: renewal recorded', [
                'user_id'    => $user->id,
                'rzp_sub_id' => $rzpSubId,
                'payment_id' => $paymentId,
                'amount'     => $amountPaid,
                'renews_at'  => $currentEnd,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);

            // Dispatch subscription renewed + payment received notification emails
            $sub      = $this->subscriptionEntity();
            $planId   = (int) data_get($sub, 'notes.plan_id');
            $plan     = \App\Models\SubscriptionPlan::find($planId);
            $payment  = $this->paymentEntity();
            $paidAt   = now()->format('M j, Y \a\t g:i A T');
            $amountFmt = '₹' . number_format(((int) data_get($payment, 'amount', 0)) / 100, 2);

            SendEmailJob::dispatch(
                template:       EmailTemplate::SubscriptionRenewed,
                data:           [
                    'user_name'        => $user->name,
                    'plan_name'        => $plan?->name ?? 'Trace.Mem Plan',
                    'amount'           => $amountFmt,
                    'renewed_at'       => $paidAt,
                    'next_renewal_at'  => $currentEnd ? Carbon::createFromTimestamp($currentEnd)->format('M j, Y') : 'To be confirmed',
                    'billing_url'      => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            )->onQueue('emails');

            SendEmailJob::dispatch(
                template:       EmailTemplate::PaymentReceived,
                data:           [
                    'user_name'  => $user->name,
                    'plan_name'  => $plan?->name ?? 'Trace.Mem Plan',
                    'amount'     => $amountFmt,
                    'payment_id' => data_get($payment, 'id') ?? 'N/A',
                    'paid_at'    => $paidAt,
                    'billing_url' => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            )->onQueue('emails');
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.completed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * All billing cycles for the subscription are complete (total_count exhausted).
     * Mark the subscription as completed/expired and refresh caches.
     *
     * Payload: payload.subscription.entity
     */
    private function handleSubscriptionCompleted(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'subscription.completed';
        $userId    = null;

        DB::transaction(function () use ($eventType, &$userId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub = $this->subscriptionEntity();

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $userSub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub) {
                $userSub->update([
                    'status'    => 'expired',
                    'is_active' => false,
                    'ends_at'   => now(),
                    'auto_renew' => false,
                ]);
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.completed]: subscription completed', [
                'user_id'    => $user->id,
                'rzp_sub_id' => data_get($sub, 'id'),
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.cancelled
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Razorpay cancelled the subscription (e.g. via API or dashboard).
     * Marks cancelled; respects any existing user-initiated cancel_at from BillingController.
     *
     * Payload: payload.subscription.entity
     */
    private function handleSubscriptionCancelled(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'subscription.cancelled';
        $userId    = null;

        DB::transaction(function () use ($eventType, &$userId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub = $this->subscriptionEntity();

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $userSub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub && ! $userSub->isCancelled()) {
                $userSub->update([
                    'status'              => 'canceled',
                    'auto_renew'          => false,
                    'cancelled_at'        => now(),
                    'cancellation_reason' => 'Subscription cancelled via Razorpay.',
                ]);
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.cancelled]: subscription cancelled', [
                'user_id'    => $user->id,
                'rzp_sub_id' => data_get($sub, 'id'),
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);

            // Dispatch subscription cancelled notification email
            $sub    = $this->subscriptionEntity();
            $planId = (int) data_get($sub, 'notes.plan_id');
            $plan   = \App\Models\SubscriptionPlan::find($planId);

            SendEmailJob::dispatch(
                template:       EmailTemplate::SubscriptionCancelled,
                data:           [
                    'user_name'       => $user->name,
                    'plan_name'       => $plan?->name ?? 'Trace.Mem Plan',
                    'cancelled_at'    => now()->format('M j, Y \a\t g:i A T'),
                    'access_ends_at'  => 'Immediately',
                    'billing_url'     => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            )->onQueue('emails');
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.paused
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Razorpay paused the subscription.
     *
     * Payload: payload.subscription.entity
     */
    private function handleSubscriptionPaused(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'subscription.paused';
        $userId    = null;

        DB::transaction(function () use ($eventType, &$userId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub = $this->subscriptionEntity();

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $userSub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub) {
                $userSub->update(['status' => 'paused']);
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.paused]: subscription paused', [
                'user_id'    => $user->id,
                'rzp_sub_id' => data_get($sub, 'id'),
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: subscription.resumed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Razorpay resumed a paused subscription.
     *
     * Payload: payload.subscription.entity
     */
    private function handleSubscriptionResumed(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'subscription.resumed';
        $userId    = null;

        DB::transaction(function () use ($eventType, &$userId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $sub = $this->subscriptionEntity();

            $user = $this->findUserFromNotes($sub, $eventType);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $userSub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub) {
                $userSub->update(['status' => 'active', 'auto_renew' => true]);
            }

            Log::info('ProcessRazorpayWebhookJob [subscription.resumed]: subscription resumed', [
                'user_id'    => $user->id,
                'rzp_sub_id' => data_get($sub, 'id'),
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: payment.captured
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A payment linked to a subscription was successfully captured.
     * Marks the BillingTransaction as paid (if not already via verifyPayment).
     *
     * Payload: payload.payment.entity
     *   id              — Razorpay payment ID (pay_xxx)
     *   subscription_id — Razorpay subscription ID (sub_xxx)
     *   amount          — integer paise
     *   currency        — 'INR'
     *   status          — 'captured'
     *   notes           — may contain user_id, plan_id, billing_cycle (from subscription creation)
     */
    private function handlePaymentCaptured(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'payment.captured';
        $userId    = null;

        DB::transaction(function () use ($eventType, &$userId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $payment   = $this->paymentEntity();
            $paymentId = data_get($payment, 'id');
            $rzpSubId  = data_get($payment, 'subscription_id');
            $amount    = (int) data_get($payment, 'amount', 0);
            $currency  = (string) data_get($payment, 'currency', 'INR');

            if (! $rzpSubId) {
                // Non-subscription payment — log only
                Log::info('ProcessRazorpayWebhookJob [payment.captured]: non-subscription payment — no action', [
                    'payment_id' => $paymentId,
                ]);
                return;
            }

            // Try to identify the user via the linked BillingTransaction
            $tx = BillingTransaction::where('provider_subscription_id', $rzpSubId)->first();

            if (! $tx) {
                Log::warning('ProcessRazorpayWebhookJob [payment.captured]: no matching transaction found', [
                    'rzp_sub_id' => $rzpSubId,
                    'payment_id' => $paymentId,
                ]);
                return;
            }

            $userId = $tx->user_id;

            // Mark pending transaction as paid (idempotent — verifyPayment may have already done this)
            if ($tx->status === 'pending') {
                $tx->update([
                    'status'                     => 'paid',
                    'provider_payment_intent_id' => $paymentId,
                    'amount_total'               => $amount ?: $tx->amount_total,
                    'currency'                   => $currency,
                    'raw_payload'                => array_merge((array) ($tx->raw_payload ?? []), [
                        'payment_captured_payload' => $payment,
                    ]),
                ]);
            }

            Log::info('ProcessRazorpayWebhookJob [payment.captured]: payment recorded', [
                'user_id'    => $userId,
                'payment_id' => $paymentId,
                'rzp_sub_id' => $rzpSubId,
                'amount'     => $amount,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: payment.failed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A payment attempt failed (declined card, bank failure, etc.).
     * Sets subscription to past_due; logs for production debugging.
     * Does NOT activate or renew any subscription.
     *
     * Payload: payload.payment.entity
     *   id              — Razorpay payment ID
     *   subscription_id — Razorpay subscription ID
     *   error_code      — machine-readable error code
     *   error_description — human-readable reason
     */
    private function handlePaymentFailed(SubscriptionCacheService $subscriptionCache): void
    {
        $eventType = 'payment.failed';
        $userId    = null;
        $rzpSubId  = null;

        DB::transaction(function () use ($eventType, &$userId, &$rzpSubId) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $payment     = $this->paymentEntity();
            $paymentId   = data_get($payment, 'id');
            $rzpSubId    = data_get($payment, 'subscription_id');
            $errorCode   = data_get($payment, 'error_code');
            $errorDesc   = data_get($payment, 'error_description');

            if (! $rzpSubId) {
                Log::warning('ProcessRazorpayWebhookJob [payment.failed]: no subscription_id in payload', [
                    'payment_id' => $paymentId,
                ]);
                return;
            }

            $tx = BillingTransaction::where('provider_subscription_id', $rzpSubId)->first();

            if (! $tx) {
                Log::warning('ProcessRazorpayWebhookJob [payment.failed]: no matching transaction', [
                    'rzp_sub_id' => $rzpSubId,
                    'payment_id' => $paymentId,
                ]);
                return;
            }

            $userId = $tx->user_id;

            // Mark subscription past_due (do NOT set is_active = false yet)
            $userSub = UserSubscription::where('user_id', $userId)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($userSub) {
                $userSub->update(['status' => 'past_due']);
            }

            // Record the failed transaction
            BillingTransaction::create([
                'user_id'                    => $userId,
                'subscription_plan_id'       => $tx->subscription_plan_id,
                'user_subscription_id'       => $userSub?->id,
                'provider'                   => 'razorpay',
                'provider_payment_intent_id' => $paymentId,
                'provider_subscription_id'   => $rzpSubId,
                'billing_cycle'              => $userSub?->billing_cycle ?? $tx->billing_cycle,
                'currency'                   => $tx->currency,
                'amount_total'               => $tx->amount_total,
                'status'                     => 'failed',
                'raw_payload'                => $this->payload,
            ]);

            Log::warning('ProcessRazorpayWebhookJob [payment.failed]: subscription set to past_due', [
                'user_id'          => $userId,
                'rzp_sub_id'       => $rzpSubId,
                'payment_id'       => $paymentId,
                'error_code'       => $errorCode,
                'error_description' => $errorDesc,
            ]);

            // Dispatch payment failed notification email (after transaction commits)
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);

            $tx        = BillingTransaction::where('provider_subscription_id', $rzpSubId ?? '')->first();
            $plan      = $tx ? \App\Models\SubscriptionPlan::find($tx->subscription_plan_id) : null;
            $amountFmt = $tx ? '₹' . number_format($tx->amount_total / 100, 2) : 'Unknown';

            SendEmailJob::dispatch(
                template:       EmailTemplate::PaymentFailed,
                data:           [
                    'user_name'          => $user->name,
                    'plan_name'          => $plan?->name ?? 'Trace.Mem Plan',
                    'amount'             => $amountFmt,
                    'failed_at'          => now()->format('M j, Y \a\t g:i A T'),
                    'error_description'  => $errorDesc ?? 'Payment declined',
                    'billing_url'        => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            )->onQueue('emails');
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: refund.processed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A refund was processed for a payment.
     * Updates the corresponding BillingTransaction status to 'refunded'.
     *
     * Payload: payload.refund.entity
     *   id         — Razorpay refund ID (rfnd_xxx)
     *   payment_id — original Razorpay payment ID (pay_xxx)
     *   amount     — refund amount in paise
     */
    private function handleRefundProcessed(): void
    {
        $eventType = 'refund.processed';

        DB::transaction(function () use ($eventType) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $refund    = data_get($this->payload, 'payload.refund.entity', []);
            $paymentId = data_get($refund, 'payment_id');
            $refundId  = data_get($refund, 'id');
            $amount    = (int) data_get($refund, 'amount', 0);

            if ($paymentId) {
                BillingTransaction::where('provider_payment_intent_id', $paymentId)
                    ->update(['status' => 'refunded']);
            }

            Log::info('ProcessRazorpayWebhookJob [refund.processed]: transaction marked refunded', [
                'payment_id'     => $paymentId,
                'refund_id'      => $refundId,
                'amount_paise'   => $amount,
            ]);

            // Dispatch refund processed notification email — find user via payment
            if ($paymentId) {
                $txForRefund = BillingTransaction::where('provider_payment_intent_id', $paymentId)->with('user')->first();
                if ($txForRefund && $txForRefund->user) {
                    $refundUser = $txForRefund->user;
                    SendEmailJob::dispatch(
                        template:       EmailTemplate::RefundProcessed,
                        data:           [
                            'user_name'     => $refundUser->name,
                            'refund_amount' => '₹' . number_format($amount / 100, 2),
                            'refund_id'     => $refundId ?? 'N/A',
                            'refunded_at'   => now()->format('M j, Y \a\t g:i A T'),
                        ],
                        recipientEmail: $refundUser->email,
                        userId:         $refundUser->id,
                        requestId:      Str::uuid()->toString(),
                    )->onQueue('emails');
                }
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: order.paid (optional)
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * An order was fully paid. Supplemental — subscription.activated is
     * the authoritative signal for entitlement activation.
     * Log-only for now.
     *
     * Payload: payload.order.entity + payload.payment.entity
     */
    private function handleOrderPaid(): void
    {
        $eventType = 'order.paid';

        DB::transaction(function () use ($eventType) {
            if (! $this->recordOrSkip($eventType)) {
                return;
            }

            $order   = data_get($this->payload, 'payload.order.entity', []);
            $payment = data_get($this->payload, 'payload.payment.entity', []);

            Log::info('ProcessRazorpayWebhookJob [order.paid]: order payment received', [
                'order_id'   => data_get($order, 'id'),
                'payment_id' => data_get($payment, 'id'),
                'amount'     => data_get($payment, 'amount'),
            ]);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: unsupported event (future-proofing)
     * ═══════════════════════════════════════════════════════════════ */

    private function handleUnsupported(string $eventType): void
    {
        Log::info('ProcessRazorpayWebhookJob: unsupported event type received', [
            'event_type' => $eventType,
            'event_id'   => substr($this->eventId, 0, 32) . '…',
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  FAILED HOOK
     * ═══════════════════════════════════════════════════════════════ */

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRazorpayWebhookJob: exhausted all retries', [
            'event_type' => $this->payload['event'] ?? 'unknown',
            'event_id'   => substr($this->eventId, 0, 32) . '…',
            'error'      => $exception->getMessage(),
        ]);
    }
}
