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
use Carbon\Carbon;
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
 *
 * Supported events (as registered in Stripe Dashboard):
 *   - checkout.session.completed
 *   - checkout.session.expired
 *   - customer.subscription.created
 *   - customer.subscription.updated
 *   - customer.subscription.deleted
 *   - customer.subscription.trial_will_end
 *   - invoice.payment_succeeded
 *   - invoice.payment_failed
 *   - invoice.upcoming
 *   - payment_method.attached
 *   - payment_method.detached
 *   - charge.refunded
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

    /* ═══════════════════════════════════════════════════════════════
     *  ENTRY POINT
     * ═══════════════════════════════════════════════════════════════ */

    public function handle(SubscriptionCacheService $subscriptionCache): void
    {
        $eventId   = $this->payload['id']             ?? null;
        $eventType = $this->payload['type']           ?? null;
        $object    = $this->payload['data']['object'] ?? [];

        if (! $eventId || ! $eventType) {
            Log::error('ProcessStripeWebhookJob: missing event_id or event_type', [
                'payload_keys' => array_keys($this->payload),
            ]);
            return;
        }

        // Route to the appropriate handler. Every branch uses the same
        // idempotency guard (stripe_webhook_events INSERT) at its top.
        match ($eventType) {
            'checkout.session.completed'         => $this->handleCheckoutCompleted($eventId, $eventType, $object, $subscriptionCache),
            'checkout.session.expired'           => $this->handleCheckoutExpired($eventId, $eventType, $object),
            'customer.subscription.created'      => $this->handleSubscriptionCreated($eventId, $eventType, $object, $subscriptionCache),
            'customer.subscription.updated'      => $this->handleSubscriptionUpdated($eventId, $eventType, $object, $subscriptionCache),
            'customer.subscription.deleted'      => $this->handleSubscriptionDeleted($eventId, $eventType, $object, $subscriptionCache),
            'customer.subscription.trial_will_end' => $this->handleSubscriptionTrialWillEnd($eventId, $eventType, $object),
            'invoice.payment_succeeded'          => $this->handleInvoicePaymentSucceeded($eventId, $eventType, $object, $subscriptionCache),
            'invoice.payment_failed'             => $this->handleInvoicePaymentFailed($eventId, $eventType, $object, $subscriptionCache),
            'invoice.upcoming'                   => $this->handleInvoiceUpcoming($eventId, $eventType, $object),
            'payment_method.attached'            => $this->handlePaymentMethodAttached($eventId, $eventType, $object),
            'payment_method.detached'            => $this->handlePaymentMethodDetached($eventId, $eventType, $object),
            'charge.refunded'                    => $this->handleChargeRefunded($eventId, $eventType, $object),
            default                              => $this->handleUnsupported($eventId, $eventType),
        };
    }

    /* ═══════════════════════════════════════════════════════════════
     *  IDEMPOTENCY GUARD (reused by every handler)
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Attempt to INSERT the event into stripe_webhook_events.
     * Returns false if the event was already processed (duplicate delivery).
     * Must be called at the top of every handler's DB::transaction.
     *
     * @throws \Throwable — re-throws non-duplicate DB errors
     */
    private function recordOrSkip(string $eventId, string $eventType, array $object): bool
    {
        try {
            DB::table('stripe_webhook_events')->insert([
                'event_id'     => $eventId,
                'event_type'   => $eventType,
                'processed_at' => now(),
                'payload_hash' => hash('sha256', json_encode($object)),
            ]);
            return true;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            Log::info('ProcessStripeWebhookJob: duplicate event, skipping', [
                'event_id'   => $eventId,
                'event_type' => $eventType,
            ]);
            return false;
        } catch (\Throwable $e) {
            // SQLite / older MySQL fallback
            if (
                str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'unique_violation')
            ) {
                Log::info('ProcessStripeWebhookJob: duplicate event (fallback check), skipping', [
                    'event_id' => $eventId,
                ]);
                return false;
            }
            throw $e;
        }
    }

    /**
     * Find a User by their Stripe customer ID.
     * Returns null if not found and logs a warning.
     */
    private function findUserByStripeCustomer(string $stripeCustomerId, string $eventType, string $eventId): ?User
    {
        $user = User::where('stripe_customer_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning("ProcessStripeWebhookJob [{$eventType}]: no user found for stripe_customer_id", [
                'stripe_customer_id' => $stripeCustomerId,
                'event_id'           => $eventId,
            ]);
        }

        return $user;
    }

    /**
     * Invalidate subscription-related caches and dispatch analytics re-aggregation.
     */
    private function invalidateCaches(User $user, SubscriptionCacheService $subscriptionCache): void
    {
        $subscriptionCache->forgetEntitlements($user);
        $subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: checkout.session.completed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A user completed Stripe Checkout and paid.
     *
     * Stripe payload: checkout.Session object
     *   metadata.user_id      — our internal user ID
     *   metadata.plan_id      — our internal subscription plan ID
     *   metadata.billing_cycle
     *   customer              — Stripe customer ID
     *   subscription          — Stripe subscription ID
     *   payment_intent        — Stripe payment intent ID
     *   amount_total          — integer cents
     *   currency
     */
    private function handleCheckoutCompleted(
        string $eventId,
        string $eventType,
        array $session,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $session, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $session)) {
                return;
            }

            $userId       = (int) data_get($session, 'metadata.user_id');
            $planId       = (int) data_get($session, 'metadata.plan_id');
            $billingCycle = (string) data_get($session, 'metadata.billing_cycle', 'monthly');

            if (! $userId || ! $planId) {
                Log::error('ProcessStripeWebhookJob [checkout.session.completed]: missing user_id or plan_id in metadata', [
                    'event_id' => $eventId,
                    'metadata' => data_get($session, 'metadata'),
                ]);
                return;
            }

            // Mark the pending BillingTransaction (created at checkout initiation) as paid.
            // We update by checkout session ID rather than creating a new row to avoid duplicates.
            $existing = BillingTransaction::where('provider_checkout_session_id', data_get($session, 'id'))->first();

            if ($existing) {
                $existing->update([
                    'status'                      => 'paid',
                    'provider_payment_intent_id'  => data_get($session, 'payment_intent'),
                    'provider_subscription_id'    => data_get($session, 'subscription'),
                    'amount_total'                => (int) data_get($session, 'amount_total', 0),
                    'raw_payload'                 => $session,
                ]);
            } else {
                // Defensive: create if pre-checkout transaction was never recorded
                BillingTransaction::create([
                    'user_id'                      => $userId,
                    'subscription_plan_id'         => $planId,
                    'provider'                     => 'stripe',
                    'provider_checkout_session_id' => data_get($session, 'id'),
                    'provider_payment_intent_id'   => data_get($session, 'payment_intent'),
                    'provider_subscription_id'     => data_get($session, 'subscription'),
                    'billing_cycle'                => $billingCycle,
                    'currency'                     => data_get($session, 'currency', 'usd'),
                    'amount_total'                 => (int) data_get($session, 'amount_total', 0),
                    'status'                       => 'paid',
                    'raw_payload'                  => $session,
                ]);
            }

            // Create or update the UserSubscription
            UserSubscription::updateOrCreate(
                [
                    'user_id'              => $userId,
                    'subscription_plan_id' => $planId,
                    'is_active'            => true,
                ],
                [
                    'billing_cycle'   => $billingCycle,
                    'status'          => 'active',
                    'starts_at'       => now(),
                    'renews_at'       => null,
                    'ends_at'         => null,
                    'auto_renew'      => true,
                    'overage_enabled' => false,
                    'quotas_snapshot' => [],
                ]
            );

            // Persist Stripe customer ID on user
            $stripeCustomerId = data_get($session, 'customer');
            if ($stripeCustomerId && ($user = User::find($userId))) {
                $user->forceFill(['stripe_customer_id' => $stripeCustomerId])->save();
            }
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: checkout.session.expired
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A Checkout Session expired before the user completed payment.
     * Mark the pending BillingTransaction as canceled.
     *
     * Stripe payload: checkout.Session object
     *   id        — Stripe checkout session ID
     *   metadata  — same structure as checkout.session.completed
     */
    private function handleCheckoutExpired(string $eventId, string $eventType, array $session): void
    {
        DB::transaction(function () use ($eventId, $eventType, $session) {
            if (! $this->recordOrSkip($eventId, $eventType, $session)) {
                return;
            }

            $sessionId = data_get($session, 'id');

            if ($sessionId) {
                BillingTransaction::where('provider_checkout_session_id', $sessionId)
                    ->where('status', 'pending')
                    ->update(['status' => 'canceled']);
            }

            Log::info('ProcessStripeWebhookJob [checkout.session.expired]: pending transaction canceled', [
                'event_id'   => $eventId,
                'session_id' => $sessionId,
            ]);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: customer.subscription.created
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Stripe created a subscription for a customer.
     * This fires after checkout.session.completed in the standard flow.
     * We use it to backfill the Stripe subscription ID and set real
     * renewal dates from Stripe (current_period_end → renews_at).
     *
     * Stripe payload: Subscription object
     *   id                    — Stripe subscription ID (sub_xxx)
     *   customer              — Stripe customer ID
     *   status                — 'active', 'trialing', 'past_due', etc.
     *   current_period_start  — Unix timestamp
     *   current_period_end    — Unix timestamp → our renews_at
     *   trial_end             — Unix timestamp or null
     *   metadata              — may contain user_id, plan_id
     */
    private function handleSubscriptionCreated(
        string $eventId,
        string $eventType,
        array $subscription,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $subscription, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $subscription)) {
                return;
            }

            $stripeCustomerId  = data_get($subscription, 'customer');
            $stripeSubId       = data_get($subscription, 'id');
            $stripeStatus      = data_get($subscription, 'status', 'active');
            $periodEnd         = data_get($subscription, 'current_period_end');
            $trialEnd          = data_get($subscription, 'trial_end');

            if (! $stripeCustomerId) {
                Log::warning('ProcessStripeWebhookJob [customer.subscription.created]: no customer ID', [
                    'event_id' => $eventId,
                ]);
                return;
            }

            $user = User::where('stripe_customer_id', $stripeCustomerId)->first();
            if (! $user) {
                Log::warning('ProcessStripeWebhookJob [customer.subscription.created]: no user for customer', [
                    'event_id'           => $eventId,
                    'stripe_customer_id' => $stripeCustomerId,
                ]);
                return;
            }

            $userId = $user->id;

            // Map Stripe status → our status enum
            $localStatus = $this->mapStripeStatus($stripeStatus);

            // Update the active subscription with real Stripe data
            $sub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($sub) {
                $sub->update([
                    'status'    => $localStatus,
                    'renews_at' => $periodEnd  ? \Carbon\Carbon::createFromTimestamp($periodEnd)  : null,
                    'ends_at'   => $trialEnd   ? \Carbon\Carbon::createFromTimestamp($trialEnd)   : null,
                ]);
            }

            // Also update the BillingTransaction with the Stripe subscription ID
            if ($stripeSubId) {
                BillingTransaction::where('user_id', $user->id)
                    ->whereNull('provider_subscription_id')
                    ->where('status', 'paid')
                    ->latest()
                    ->limit(1)
                    ->update(['provider_subscription_id' => $stripeSubId]);
            }

            Log::info('ProcessStripeWebhookJob [customer.subscription.created]: subscription enriched', [
                'event_id'    => $eventId,
                'user_id'     => $user->id,
                'stripe_sub'  => $stripeSubId,
                'renews_at'   => $periodEnd,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: customer.subscription.updated
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A subscription's status or billing period changed.
     * The most common triggers: trial → active, active → past_due,
     * plan upgrade/downgrade, renewal (current_period_end advances).
     *
     * Stripe payload: Subscription object (same structure as .created)
     */
    private function handleSubscriptionUpdated(
        string $eventId,
        string $eventType,
        array $subscription,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $subscription, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $subscription)) {
                return;
            }

            $stripeCustomerId = data_get($subscription, 'customer');
            $stripeStatus     = data_get($subscription, 'status', 'active');
            $periodEnd        = data_get($subscription, 'current_period_end');
            $canceledAt       = data_get($subscription, 'canceled_at');

            if (! $stripeCustomerId) {
                return;
            }

            $user = $this->findUserByStripeCustomer($stripeCustomerId, $eventType, $eventId);
            if (! $user) {
                return;
            }

            $userId      = $user->id;
            $localStatus = $this->mapStripeStatus($stripeStatus);

            $sub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if (! $sub) {
                Log::warning('ProcessStripeWebhookJob [customer.subscription.updated]: no active subscription found', [
                    'event_id' => $eventId,
                    'user_id'  => $user->id,
                ]);
                return;
            }

            $updateData = [
                'status'    => $localStatus,
                'renews_at' => $periodEnd ? \Carbon\Carbon::createFromTimestamp($periodEnd) : $sub->renews_at,
            ];

            // If Stripe canceled the subscription server-side (e.g. failed payment exhausted)
            if ($canceledAt && ! $sub->cancelled_at) {
                $updateData['cancelled_at']        = \Carbon\Carbon::createFromTimestamp($canceledAt);
                $updateData['cancellation_reason'] = 'Subscription canceled by Stripe (payment failure or admin action).';
                $updateData['auto_renew']          = false;
            }

            $sub->update($updateData);

            Log::info('ProcessStripeWebhookJob [customer.subscription.updated]', [
                'event_id'     => $eventId,
                'user_id'      => $user->id,
                'new_status'   => $localStatus,
                'renews_at'    => $periodEnd,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: customer.subscription.deleted
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * The subscription has ended — either the user canceled, or the
     * subscription reached its end date, or payment failed too many times.
     *
     * Stripe payload: Subscription object
     *   ended_at — Unix timestamp when it ended
     */
    private function handleSubscriptionDeleted(
        string $eventId,
        string $eventType,
        array $subscription,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $subscription, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $subscription)) {
                return;
            }

            $stripeCustomerId = data_get($subscription, 'customer');
            $endedAt          = data_get($subscription, 'ended_at');

            if (! $stripeCustomerId) {
                return;
            }

            $user = $this->findUserByStripeCustomer($stripeCustomerId, $eventType, $eventId);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $sub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($sub) {
                $sub->update([
                    'status'    => 'expired',
                    'is_active' => false,
                    'ends_at'   => $endedAt ? \Carbon\Carbon::createFromTimestamp($endedAt) : now(),
                    'auto_renew' => false,
                ]);
            }

            Log::info('ProcessStripeWebhookJob [customer.subscription.deleted]: subscription expired', [
                'event_id' => $eventId,
                'user_id'  => $user->id,
                'ended_at' => $endedAt,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: customer.subscription.trial_will_end
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Fires 3 days before trial ends (or immediately for early-ended trials).
     * Log-only for now. Future: send trial-ending email notification.
     *
     * Stripe payload: Subscription object
     *   trial_end — Unix timestamp
     */
    private function handleSubscriptionTrialWillEnd(string $eventId, string $eventType, array $subscription): void
    {
        DB::transaction(function () use ($eventId, $eventType, $subscription) {
            if (! $this->recordOrSkip($eventId, $eventType, $subscription)) {
                return;
            }

            $stripeCustomerId = data_get($subscription, 'customer');
            $trialEnd         = data_get($subscription, 'trial_end');

            Log::info('ProcessStripeWebhookJob [customer.subscription.trial_will_end]: trial ending soon', [
                'event_id'           => $eventId,
                'stripe_customer_id' => $stripeCustomerId,
                'trial_end'          => $trialEnd
                    ? \Carbon\Carbon::createFromTimestamp($trialEnd)->toIso8601String()
                    : null,
            ]);

            // TODO: dispatch a TrialEndingNotification email job here
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: invoice.payment_succeeded
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * An invoice was paid. This fires for EVERY successful invoice payment,
     * including the first payment (which is also covered by checkout.session.completed).
     *
     * We skip recording a BillingTransaction when a checkout session ID exists on
     * the invoice — that payment is already recorded by checkout.session.completed.
     * For recurring renewal invoices (no checkout session), we record a new transaction.
     *
     * Stripe payload: Invoice object
     *   subscription          — Stripe subscription ID
     *   customer              — Stripe customer ID
     *   amount_paid           — integer cents
     *   currency
     *   id                    — Stripe invoice ID (inv_xxx)
     *   payment_intent        — Stripe payment intent ID
     *   lines.data[0].price.id — Stripe price ID
     *   billing_reason        — 'subscription_create', 'subscription_cycle', etc.
     */
    private function handleInvoicePaymentSucceeded(
        string $eventId,
        string $eventType,
        array $invoice,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $invoice, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $invoice)) {
                return;
            }

            $billingReason    = data_get($invoice, 'billing_reason');
            $stripeCustomerId = data_get($invoice, 'customer');
            $invoiceId        = data_get($invoice, 'id');
            $amountPaid       = (int) data_get($invoice, 'amount_paid', 0);
            $currency         = data_get($invoice, 'currency', 'usd');
            $paymentIntent    = data_get($invoice, 'payment_intent');
            $stripeSubId      = data_get($invoice, 'subscription');
            $periodEnd        = data_get($invoice, 'lines.data.0.period.end');

            // Skip the first payment — checkout.session.completed already recorded it.
            // billing_reason = 'subscription_create' fires on the first invoice.
            if ($billingReason === 'subscription_create') {
                Log::info('ProcessStripeWebhookJob [invoice.payment_succeeded]: first invoice — skipping (handled by checkout.session.completed)', [
                    'event_id'   => $eventId,
                    'invoice_id' => $invoiceId,
                ]);
                return;
            }

            if (! $stripeCustomerId) {
                return;
            }

            $user = $this->findUserByStripeCustomer($stripeCustomerId, $eventType, $eventId);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            // Find the active subscription to link the transaction
            $sub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            // Record the renewal transaction
            BillingTransaction::create([
                'user_id'                    => $user->id,
                'subscription_plan_id'       => $sub?->subscription_plan_id ?? 0,
                'user_subscription_id'       => $sub?->id,
                'provider'                   => 'stripe',
                'provider_invoice_id'        => $invoiceId,
                'provider_payment_intent_id' => $paymentIntent,
                'provider_subscription_id'   => $stripeSubId,
                'billing_cycle'              => $sub?->billing_cycle ?? 'monthly',
                'currency'                   => $currency,
                'amount_total'               => $amountPaid,
                'status'                     => 'paid',
                'raw_payload'                => $invoice,
            ]);

            // Advance renews_at for the subscription
            if ($sub && $periodEnd) {
                $sub->update([
                    'status'    => 'active',
                    'renews_at' => \Carbon\Carbon::createFromTimestamp($periodEnd),
                ]);
            }

            Log::info('ProcessStripeWebhookJob [invoice.payment_succeeded]: renewal recorded', [
                'event_id'   => $eventId,
                'user_id'    => $user->id,
                'invoice_id' => $invoiceId,
                'amount'     => $amountPaid,
            ]);
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: invoice.payment_failed
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * An invoice payment attempt failed (declined card, expired card, etc.)
     * Update subscription status to past_due.
     *
     * Stripe payload: Invoice object
     *   subscription      — Stripe subscription ID
     *   customer          — Stripe customer ID
     *   attempt_count     — how many times payment was attempted
     *   next_payment_attempt — Unix timestamp for next retry
     */
    private function handleInvoicePaymentFailed(
        string $eventId,
        string $eventType,
        array $invoice,
        SubscriptionCacheService $subscriptionCache,
    ): void {
        $userId = null;

        DB::transaction(function () use ($eventId, $eventType, $invoice, &$userId) {
            if (! $this->recordOrSkip($eventId, $eventType, $invoice)) {
                return;
            }

            $stripeCustomerId   = data_get($invoice, 'customer');
            $attemptCount       = (int) data_get($invoice, 'attempt_count', 1);
            $nextPaymentAttempt = data_get($invoice, 'next_payment_attempt');

            if (! $stripeCustomerId) {
                return;
            }

            $user = $this->findUserByStripeCustomer($stripeCustomerId, $eventType, $eventId);
            if (! $user) {
                return;
            }

            $userId = $user->id;

            $sub = UserSubscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('starts_at')
                ->first();

            if ($sub) {
                $sub->update(['status' => 'past_due']);
            }

            Log::warning('ProcessStripeWebhookJob [invoice.payment_failed]: subscription set to past_due', [
                'event_id'            => $eventId,
                'user_id'             => $user->id,
                'attempt_count'       => $attemptCount,
                'next_payment_attempt' => $nextPaymentAttempt
                    ? \Carbon\Carbon::createFromTimestamp($nextPaymentAttempt)->toIso8601String()
                    : 'none',
            ]);

            // TODO: dispatch PaymentFailedNotification email job here
        });

        if ($userId && ($user = User::find($userId))) {
            $this->invalidateCaches($user, $subscriptionCache);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: invoice.upcoming
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Fires X days before a subscription auto-renews.
     * The Invoice object has NO id field (per Stripe docs).
     * Log-only. Future: send upcoming renewal notification email.
     *
     * Stripe payload: Invoice object (no id)
     *   subscription       — Stripe subscription ID
     *   customer           — Stripe customer ID
     *   amount_due         — integer cents
     *   next_payment_attempt — Unix timestamp
     */
    private function handleInvoiceUpcoming(string $eventId, string $eventType, array $invoice): void
    {
        DB::transaction(function () use ($eventId, $eventType, $invoice) {
            if (! $this->recordOrSkip($eventId, $eventType, $invoice)) {
                return;
            }

            $stripeCustomerId   = data_get($invoice, 'customer');
            $amountDue          = (int) data_get($invoice, 'amount_due', 0);
            $nextPaymentAttempt = data_get($invoice, 'next_payment_attempt');

            Log::info('ProcessStripeWebhookJob [invoice.upcoming]: renewal reminder', [
                'event_id'            => $eventId,
                'stripe_customer_id'  => $stripeCustomerId,
                'amount_due_cents'    => $amountDue,
                'next_payment_attempt' => $nextPaymentAttempt
                    ? \Carbon\Carbon::createFromTimestamp($nextPaymentAttempt)->toIso8601String()
                    : null,
            ]);

            // TODO: dispatch UpcomingRenewalNotification email job here
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: payment_method.attached
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A payment method was attached to a customer.
     * Log-only — no DB changes needed.
     *
     * Stripe payload: PaymentMethod object
     *   id       — Stripe payment method ID (pm_xxx)
     *   customer — Stripe customer ID
     *   type     — 'card', 'us_bank_account', etc.
     */
    private function handlePaymentMethodAttached(string $eventId, string $eventType, array $paymentMethod): void
    {
        DB::transaction(function () use ($eventId, $eventType, $paymentMethod) {
            if (! $this->recordOrSkip($eventId, $eventType, $paymentMethod)) {
                return;
            }

            Log::info('ProcessStripeWebhookJob [payment_method.attached]', [
                'event_id'           => $eventId,
                'stripe_customer_id' => data_get($paymentMethod, 'customer'),
                'payment_method_id'  => data_get($paymentMethod, 'id'),
                'type'               => data_get($paymentMethod, 'type'),
            ]);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: payment_method.detached
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A payment method was detached from a customer.
     * Log-only — no DB changes needed.
     * Note: customer field is null on detach (Stripe behavior).
     *
     * Stripe payload: PaymentMethod object
     *   id       — Stripe payment method ID
     *   customer — null after detach
     *   type     — 'card', etc.
     */
    private function handlePaymentMethodDetached(string $eventId, string $eventType, array $paymentMethod): void
    {
        DB::transaction(function () use ($eventId, $eventType, $paymentMethod) {
            if (! $this->recordOrSkip($eventId, $eventType, $paymentMethod)) {
                return;
            }

            Log::info('ProcessStripeWebhookJob [payment_method.detached]', [
                'event_id'          => $eventId,
                'payment_method_id' => data_get($paymentMethod, 'id'),
                'type'              => data_get($paymentMethod, 'type'),
            ]);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: charge.refunded
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * A charge was refunded (full or partial).
     * Update the corresponding BillingTransaction status to 'refunded'.
     *
     * Stripe payload: Charge object
     *   payment_intent    — Stripe payment intent ID → links to our BillingTransaction
     *   amount_refunded   — integer cents
     *   refunds.data[0].id — Stripe refund ID (re_xxx)
     *   currency
     */
    private function handleChargeRefunded(string $eventId, string $eventType, array $charge): void
    {
        DB::transaction(function () use ($eventId, $eventType, $charge) {
            if (! $this->recordOrSkip($eventId, $eventType, $charge)) {
                return;
            }

            $paymentIntentId = data_get($charge, 'payment_intent');
            $amountRefunded  = (int) data_get($charge, 'amount_refunded', 0);
            $refundId        = data_get($charge, 'refunds.data.0.id');

            if ($paymentIntentId) {
                BillingTransaction::where('provider_payment_intent_id', $paymentIntentId)
                    ->update(['status' => 'refunded']);
            }

            Log::info('ProcessStripeWebhookJob [charge.refunded]', [
                'event_id'         => $eventId,
                'payment_intent'   => $paymentIntentId,
                'amount_refunded'  => $amountRefunded,
                'refund_id'        => $refundId,
            ]);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HANDLER: unsupported event (future-proofing)
     * ═══════════════════════════════════════════════════════════════ */

    private function handleUnsupported(string $eventId, string $eventType): void
    {
        Log::info('ProcessStripeWebhookJob: unsupported event type received', [
            'event_type' => $eventType,
            'event_id'   => $eventId,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  HELPERS
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Map Stripe subscription status strings to our DB enum values.
     *
     * Stripe statuses: active, past_due, canceled, unpaid, incomplete,
     *                  incomplete_expired, trialing, paused
     * Our enum: trial, active, past_due, canceled, expired
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active'               => 'active',
            'trialing'             => 'trial',
            'past_due', 'unpaid'   => 'past_due',
            'canceled'             => 'canceled',
            'incomplete_expired'   => 'expired',
            default                => 'active', // 'incomplete', 'paused' → keep as active
        };
    }

    /* ═══════════════════════════════════════════════════════════════
     *  FAILED HOOK
     * ═══════════════════════════════════════════════════════════════ */

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessStripeWebhookJob: exhausted all retries', [
            'event_id'   => $this->payload['id']   ?? 'unknown',
            'event_type' => $this->payload['type'] ?? 'unknown',
            'error'      => $exception->getMessage(),
        ]);
    }
}
