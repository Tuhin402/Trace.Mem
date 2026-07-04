<?php

namespace App\Http\Controllers;

use App\Enums\EmailTemplate;
use App\Jobs\AggregateUsageStatsJob;
use App\Jobs\SendEmailJob;
use App\Models\BillingTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Auth\SubscriptionCacheService;
use App\Services\Billing\BillingCatalogService;
use App\Services\Billing\FreeTrialAnalyticsService;
use App\Services\Billing\FreeTrialEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService       $catalog,
        private readonly SubscriptionCacheService    $subscriptionCache,
        private readonly FreeTrialEligibilityService $trialService,
        private readonly FreeTrialAnalyticsService   $trialAnalytics,
    ) {}

    /* ════════════════════════════════════════════════════════════
     *  INDEX — billing dashboard page
     * ════════════════════════════════════════════════════════════ */

    public function index(Request $request)
    {
        $user = $request->user();

        $subscription = $user?->currentSubscription;
        $plan         = $subscription?->subscriptionPlan;

        $cancelledSub = null;
        if (! $subscription && $user) {
            $cancelledSub = $user->subscriptions()
                ->whereNotNull('cancelled_at')
                ->latest('cancelled_at')
                ->first();
        }

        // Use the active sub, or fall back to the cancelled one for display
        $displaySub = $subscription ?? $cancelledSub;

        // Count active (non-revoked) API keys for the quota display
        $activeKeyCount = $user
            ? $user->apiKeys()->whereNull('revoked_at')->count()
            : 0;

        // Total API requests made (from usage logs) for the quota display
        $totalRequests = 0;
        if ($user) {
            $keyIds = $user->apiKeys()->pluck('id');
            if ($keyIds->isNotEmpty()) {
                $totalRequests = \App\Models\ApiUsageLog::whereIn('api_key_id', $keyIds)->count();
            }
        }

        // ── Trial info (all amounts from DB — never hardcoded) ─────────────
        $trialInfo = $user ? $this->trialService->getTrialInfo($user) : null;

        return Inertia::render('app/Billing', [
            'plan'         => $plan,
            'plans'        => $this->catalog->activePlans(),
            'subscription' => $displaySub
                ? [
                    'id'                     => $displaySub->id,
                    'status'                 => $displaySub->status,
                    'billing_cycle'          => $displaySub->billing_cycle,
                    'starts_at'              => $displaySub->starts_at?->format('M j, Y'),
                    'renews_at'              => $displaySub->renews_at?->format('M j, Y'),
                    'ends_at'                => $displaySub->ends_at?->format('M j, Y'),
                    'auto_renew'             => $displaySub->auto_renew,
                    'cancelled_at'           => $displaySub->cancelled_at?->format('M j, Y \a\t g:i A'),
                    'cancellation_reason'    => $displaySub->cancellation_reason,
                    'is_cancelled'           => $displaySub->isCancelled(),
                    'is_trial_subscription'  => $trialInfo['is_in_trial'] ?? false,
                ]
                : null,
            'usage'        => [
                'active_keys'    => $activeKeyCount,
                'total_requests' => $totalRequests,
            ],
            'trial_info'   => $trialInfo,
            'flash'        => [
                'message' => session('message'),
                'error'   => session('error'),
            ],
        ]);
    }

    /* ════════════════════════════════════════════════════════════
     *  CANCEL SUBSCRIPTION
     * ════════════════════════════════════════════════════════════ */

    public function cancelSubscription(Request $request)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user         = $request->user();
        $subscription = $user->subscriptions()
            ->where('is_active', true)
            ->whereNull('cancelled_at')
            ->latest('starts_at')
            ->first();

        if (! $subscription) {
            return redirect()
                ->route('billing.index')
                ->with('error', 'No active subscription found to cancel.');
        }

        $subscription->update([
            'cancelled_at'        => now(),
            'cancellation_reason' => $data['reason'],
            'status'              => 'canceled',
            'is_active'           => false,
            'auto_renew'          => false,
        ]);

        // ── If user cancels during a free trial, mark offer as permanently consumed ──
        $freshUser = $user->fresh();
        if ($freshUser && $freshUser->free_trial_status === 'activated') {
            $freshUser->forceFill(['free_trial_status' => 'cancelled'])->save();
            $this->trialAnalytics->track($freshUser, 'trial_cancelled', [
                'reason' => $data['reason'],
            ]);
        }

        // ── Invalidate caches + dispatch analytics job ─────────────────────
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        return redirect()
            ->route('billing.index')
            ->with('message', 'Your subscription has been cancelled. You will lose access to paid features immediately.');
    }

    /* ════════════════════════════════════════════════════════════
     *  CHECKOUT — creates Razorpay Subscription
     *
     *  Standard flow (existing, unchanged):
     *    1. Resolve / lazily create a Razorpay Plan for this plan+cycle
     *    2. Create a Razorpay Subscription for the user
     *    3. Persist a pending BillingTransaction
     *    4. Return JSON to the frontend for Razorpay modal initialisation
     *
     *  Founding Offer (trial) additions:
     *    - canActivate() gate with lockForUpdate race-condition protection
     *    - start_at = now() + 1 month added to Razorpay subscription payload
     *    - pending_activation state set before API call; activated/reset after
     *    - Upgrade path: cancel old Razorpay trial subscription if present
     * ════════════════════════════════════════════════════════════ */

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_slug'     => ['required', 'string'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $cycle  = $data['billing_cycle'];
        $amount = match ($cycle) {
            'monthly'   => $plan->price_monthly,
            'quarterly' => $plan->price_quarterly,
            'yearly'    => $plan->price_yearly,
        };

        $amountPaise = (int) round(((float) $amount) * 100);

        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        $user      = $request->user();

        // ── Detect trial eligibility ──────────────────────────────────────
        $isTrial        = false;
        $isUpgradeFromTrial = false;
        $trialEndsAt    = null;

        if ($this->trialService->canActivate($user, $plan->slug, $cycle)) {
            $isTrial     = true;
            $trialEndsAt = now()->addMonth();

            // ── Race-condition guard: lockForUpdate inside transaction ─────
            try {
                DB::transaction(function () use ($user, $plan, $cycle) {
                    $freshUser = User::lockForUpdate()->find($user->id);

                    // Re-check eligibility under lock (prevents double activation)
                    if (! $this->trialService->canActivate($freshUser, $plan->slug, $cycle)) {
                        throw new \RuntimeException('trial_not_eligible');
                    }

                    // Set transient pending state to block concurrent requests
                    $freshUser->forceFill(['free_trial_status' => 'pending_activation'])->save();
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'trial_not_eligible') {
                    Log::info('BillingController::checkout — trial already consumed (race condition guard)', [
                        'user_id' => $user->id,
                        'plan'    => $plan->slug,
                    ]);
                    // Not eligible — proceed as a normal (non-trial) checkout
                    $isTrial = false;
                } else {
                    throw $e;
                }
            }
        } elseif (
            $user->free_trial_status === 'activated'
            && $plan->slug !== FreeTrialEligibilityService::TRIAL_PLAN_SLUG
        ) {
            // ── Upgrade during trial: user is on trial and switching to a different plan ──
            $isUpgradeFromTrial = true;

            DB::transaction(function () use ($user) {
                User::lockForUpdate()->find($user->id)
                    ->forceFill(['free_trial_status' => 'pending_activation'])->save();
            });

            // Try to cancel the existing Razorpay trial subscription
            $this->cancelExistingTrialRazorpaySubscription($user, $keyId, $keySecret);
        }

        // ── Step 1: Resolve or lazily create a Razorpay Plan ──────────────
        $razorpayPlanIds = (array) ($plan->razorpay_plan_ids ?? []);
        $razorpayPlanId  = $razorpayPlanIds[$cycle] ?? null;

        if (! $razorpayPlanId) {
            [$rzpPeriod, $rzpInterval] = match ($cycle) {
                'monthly'   => ['monthly', 1],
                'quarterly' => ['monthly', 3],
                'yearly'    => ['yearly', 1],
            };

            $planResponse = Http::withBasicAuth($keyId, $keySecret)
                ->post('https://api.razorpay.com/v1/plans', [
                    'period'   => $rzpPeriod,
                    'interval' => $rzpInterval,
                    'item'     => [
                        'name'     => $plan->name . ' — ' . ucfirst($cycle),
                        'amount'   => $amountPaise,
                        'currency' => 'INR',
                    ],
                ]);

            if (! $planResponse->successful()) {
                Log::error('BillingController::checkout — Razorpay plan creation failed', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'cycle'   => $cycle,
                    'status'  => $planResponse->status(),
                    'body'    => $planResponse->json(),
                ]);

                $this->rollbackTrialPendingState($user, $isTrial, $isUpgradeFromTrial);

                return response()->json([
                    'error' => 'Payment provider is unavailable. Please try again shortly.',
                ], 502);
            }

            $razorpayPlanId          = $planResponse->json('id');
            $razorpayPlanIds[$cycle] = $razorpayPlanId;
            $plan->forceFill(['razorpay_plan_ids' => $razorpayPlanIds])->save();

            Log::info('BillingController::checkout — Razorpay plan created and cached', [
                'rzp_plan_id'   => $razorpayPlanId,
                'plan_slug'     => $plan->slug,
                'billing_cycle' => $cycle,
            ]);
        }

        // ── Step 2: Create Razorpay Subscription ──────────────────────────
        $totalCount = match ($cycle) {
            'monthly'   => 120,
            'quarterly' => 40,
            'yearly'    => 10,
        };

        $subscriptionPayload = [
            'plan_id'         => $razorpayPlanId,
            'total_count'     => $totalCount,
            'quantity'        => 1,
            'customer_notify' => 0,
            'notes'           => [
                'user_id'       => (string) $user->id,
                'plan_id'       => (string) $plan->id,
                'billing_cycle' => $cycle,
            ],
        ];

        // Add trial start_at for the Founding Offer (official Razorpay mechanism)
        if ($isTrial && $trialEndsAt) {
            $subscriptionPayload['start_at'] = $trialEndsAt->timestamp;
            $subscriptionPayload['notes']['is_trial']      = '1';
            $subscriptionPayload['notes']['trial_ends_at'] = (string) $trialEndsAt->timestamp;
        }

        $subscriptionResponse = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/subscriptions', $subscriptionPayload);

        if (! $subscriptionResponse->successful()) {
            Log::error('BillingController::checkout — Razorpay subscription creation failed', [
                'user_id'     => $user->id,
                'rzp_plan_id' => $razorpayPlanId,
                'status'      => $subscriptionResponse->status(),
                'body'        => $subscriptionResponse->json(),
            ]);

            // ── Razorpay failed: do NOT permanently consume the trial ──────
            $this->rollbackTrialPendingState($user, $isTrial, $isUpgradeFromTrial);

            return response()->json([
                'error' => 'Payment provider is unavailable. Please try again shortly.',
            ], 502);
        }

        $rzpSubscription = $subscriptionResponse->json();

        // ── Step 3: Persist pending BillingTransaction ────────────────────
        $txMetadata = [];
        if ($isTrial) {
            $txMetadata['is_trial']      = true;
            $txMetadata['trial_ends_at'] = $trialEndsAt?->timestamp;
            $txMetadata['trial_plan_id'] = $plan->id;
        }
        if ($isUpgradeFromTrial) {
            $txMetadata['upgrade_from_trial'] = true;
        }

        BillingTransaction::create([
            'user_id'                  => $user->id,
            'subscription_plan_id'     => $plan->id,
            'provider'                 => 'razorpay',
            'provider_subscription_id' => $rzpSubscription['id'],
            'billing_cycle'            => $cycle,
            'currency'                 => 'INR',
            'amount_total'             => $amountPaise,
            'status'                   => 'pending',
            'raw_payload'              => $rzpSubscription,
            'metadata'                 => $txMetadata ?: null,
        ]);

        // ── Analytics tracking ────────────────────────────────────────────
        $this->trialAnalytics->track($user, 'trial_started', [
            'plan_slug'       => $plan->slug,
            'billing_cycle'   => $cycle,
            'is_trial'        => $isTrial,
            'is_upgrade'      => $isUpgradeFromTrial,
            'rzp_sub_id'      => $rzpSubscription['id'],
        ]);

        Log::info('BillingController::checkout — pending transaction created', [
            'user_id'             => $user->id,
            'rzp_subscription_id' => $rzpSubscription['id'],
            'plan_slug'           => $plan->slug,
            'billing_cycle'       => $cycle,
            'amount_paise'        => $amountPaise,
            'is_trial'            => $isTrial,
            'is_upgrade'          => $isUpgradeFromTrial,
        ]);

        // ── Step 4: Return order details for Razorpay modal ───────────────
        return response()->json([
            'subscription_id' => $rzpSubscription['id'],
            'key_id'          => $keyId,
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'name'            => 'TraceMem',
            'description'     => 'Memory Infrastructure for AI — ' . $plan->name . ' (' . ucfirst($cycle) . ')',
            'prefill'         => [
                'email' => $user->email,
                'name'  => $user->name,
            ],
            'is_trial'        => $isTrial,
        ]);
    }

    /* ════════════════════════════════════════════════════════════
     *  VERIFY PAYMENT — server-side signature verification
     *
     *  Unchanged from original for non-trial flow.
     *  Trial additions:
     *    - Activates free_trial_status from pending_activation → activated
     *    - Deactivates old trial subscription on upgrade
     *    - Sends FreeTrialStarted email (price from DB, never hardcoded)
     * ════════════════════════════════════════════════════════════ */

    public function verifyPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_payment_id'      => ['required', 'string'],
            'razorpay_subscription_id' => ['required', 'string'],
            'razorpay_signature'       => ['required', 'string'],
        ]);

        $keySecret = config('services.razorpay.key_secret');

        // Razorpay signature: HMAC-SHA256(payment_id + "|" + subscription_id, key_secret)
        $expectedSignature = hash_hmac(
            'sha256',
            $data['razorpay_payment_id'] . '|' . $data['razorpay_subscription_id'],
            $keySecret
        );

        if (! hash_equals($expectedSignature, $data['razorpay_signature'])) {
            Log::warning('BillingController::verifyPayment — signature mismatch (possible tampering)', [
                'user_id'         => $request->user()->id,
                'subscription_id' => $data['razorpay_subscription_id'],
            ]);

            return response()->json([
                'error' => 'Payment verification failed. Please contact support if you believe this is an error.',
            ], 422);
        }

        $user      = $request->user();
        $planId    = null;
        $subPlanId = null;
        $cycle     = null;
        $isTrial   = false;
        $trialEndsUnix = null;
        $isUpgrade = false;

        DB::transaction(function () use ($user, $data, &$planId, &$subPlanId, &$cycle, &$isTrial, &$trialEndsUnix, &$isUpgrade) {
            // Locate the pending BillingTransaction created at checkout
            $tx = BillingTransaction::where('provider_subscription_id', $data['razorpay_subscription_id'])
                ->where('user_id', $user->id)
                ->where('provider', 'razorpay')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $tx) {
                // Transaction already processed (webhook arrived first) — safe no-op
                Log::info('BillingController::verifyPayment — no pending transaction found (webhook may have processed first)', [
                    'user_id'         => $user->id,
                    'subscription_id' => $data['razorpay_subscription_id'],
                ]);
                return;
            }

            $subPlanId = $tx->subscription_plan_id;
            $cycle     = $tx->billing_cycle;

            // Read trial metadata stored at checkout time
            $txMeta        = (array) ($tx->metadata ?? []);
            $isTrial       = (bool) ($txMeta['is_trial'] ?? false);
            $isUpgrade     = (bool) ($txMeta['upgrade_from_trial'] ?? false);
            $trialEndsUnix = $isTrial ? ($txMeta['trial_ends_at'] ?? null) : null;

            // Mark transaction paid and record payment ID
            $tx->update([
                'status'                     => 'paid',
                'provider_payment_intent_id' => $data['razorpay_payment_id'],
                'raw_payload'                => array_merge((array) ($tx->raw_payload ?? []), [
                    'razorpay_payment_id' => $data['razorpay_payment_id'],
                    'razorpay_signature'  => $data['razorpay_signature'],
                    'verified_at'         => now()->toIso8601String(),
                ]),
            ]);

            // ── Deactivate OLD subscriptions when upgrading from trial ────
            // This covers the case where a trial user subscribes to a different plan.
            if ($isUpgrade) {
                UserSubscription::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where('subscription_plan_id', '!=', $subPlanId)
                    ->update([
                        'is_active'  => false,
                        'status'     => 'superseded',
                        'ends_at'    => now(),
                        'auto_renew' => false,
                    ]);
            }

            // Activate the UserSubscription (idempotent via updateOrCreate)
            $userSub = UserSubscription::updateOrCreate(
                [
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $subPlanId,
                    'is_active'            => true,
                ],
                [
                    'billing_cycle'   => $cycle,
                    'status'          => 'active',
                    'starts_at'       => now(),
                    'renews_at'       => $trialEndsUnix ? \Carbon\Carbon::createFromTimestamp($trialEndsUnix) : null,
                    'ends_at'         => null,
                    'auto_renew'      => true,
                    'overage_enabled' => false,
                    'quotas_snapshot' => [],
                ]
            );

            // Link the transaction to the subscription
            $tx->update(['user_subscription_id' => $userSub->id]);

            // ── Activate trial on user if this was a trial checkout ───────
            $freshUser = User::lockForUpdate()->find($user->id);
            if ($freshUser && $isTrial && $freshUser->free_trial_status === 'pending_activation') {
                $freshUser->forceFill([
                    'free_trial_status'      => 'activated',
                    'free_trial_activated_at'=> now(),
                    'free_trial_ends_at'     => $trialEndsUnix ? \Carbon\Carbon::createFromTimestamp($trialEndsUnix) : now()->addMonth(),
                    'free_trial_plan_id'     => $subPlanId,
                ])->save();
            }

            // ── Mark trial as upgraded if this was an upgrade checkout ────
            if ($freshUser && $isUpgrade && $freshUser->free_trial_status === 'pending_activation') {
                $freshUser->forceFill(['free_trial_status' => 'upgraded'])->save();
            }
        });

        // ── Post-transaction: send emails, refresh caches ─────────────────
        $this->subscriptionCache->forgetEntitlements($user);
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        // ── Send appropriate email ────────────────────────────────────────
        $freshUser = $user->fresh();
        if ($freshUser) {
            if ($isTrial) {
                // Founding Offer started — price from DB, never hardcoded
                $plan = SubscriptionPlan::find($subPlanId);
                $trialEndFormatted = $trialEndsUnix
                    ? \Carbon\Carbon::createFromTimestamp($trialEndsUnix)->format('M j, Y')
                    : now()->addMonth()->format('M j, Y');

                SendEmailJob::dispatch(
                    template:       EmailTemplate::FreeTrialStarted,
                    data:           [
                        'user_name'           => $freshUser->name,
                        'plan_name'           => $plan?->name ?? 'Semantic Starter',
                        'trial_end_date'      => $trialEndFormatted,
                        'next_billing_date'   => $trialEndFormatted,
                        'next_billing_amount' => '₹' . number_format((float) ($plan?->price_monthly ?? 0), 0),
                        'dashboard_url'       => url('/dashboard'),
                        'billing_url'         => url('/billing'),
                    ],
                    recipientEmail: $freshUser->email,
                    userId:         $freshUser->id,
                    requestId:      Str::uuid()->toString(),
                )->onQueue('emails');

                $this->trialAnalytics->track($freshUser, 'trial_activated', [
                    'rzp_subscription_id' => $data['razorpay_subscription_id'],
                    'plan_id'             => $subPlanId,
                    'trial_ends_at'       => $trialEndsUnix,
                ]);
            } elseif ($isUpgrade) {
                $this->trialAnalytics->track($freshUser, 'trial_upgraded', [
                    'new_plan_id'   => $subPlanId,
                    'billing_cycle' => $cycle,
                ]);
            }
        }

        Log::info('BillingController::verifyPayment — subscription activated', [
            'user_id'         => $user->id,
            'subscription_id' => $data['razorpay_subscription_id'],
            'payment_id'      => $data['razorpay_payment_id'],
            'plan_id'         => $subPlanId,
            'billing_cycle'   => $cycle,
            'is_trial'        => $isTrial,
            'is_upgrade'      => $isUpgrade,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully.',
        ]);
    }

    /* ════════════════════════════════════════════════════════════
     *  SUCCESS / CANCEL — legacy redirect helpers (kept for parity)
     * ════════════════════════════════════════════════════════════ */

    public function success()
    {
        return redirect()->route('dashboard')->with('message', 'Payment completed. Your subscription will be activated shortly.');
    }

    public function cancel()
    {
        return redirect()->route('api.keys')->with('message', 'Checkout canceled.');
    }

    /* ════════════════════════════════════════════════════════════
     *  PRIVATE HELPERS
     * ════════════════════════════════════════════════════════════ */

    /**
     * If Razorpay fails, reset free_trial_status back to null so the
     * user remains eligible for the trial. Never permanently consume
     * the trial due to a payment provider error.
     */
    private function rollbackTrialPendingState(User $user, bool $isTrial, bool $isUpgradeFromTrial): void
    {
        if ($isTrial || $isUpgradeFromTrial) {
            $freshUser = $user->fresh();
            if ($freshUser && $freshUser->free_trial_status === 'pending_activation') {
                // For trial: reset to null (eligible again)
                // For upgrade: restore to 'activated' (trial was live before upgrade attempt)
                $freshUser->forceFill([
                    'free_trial_status' => $isUpgradeFromTrial ? 'activated' : null,
                ])->save();

                Log::info('BillingController: rolled back trial pending state after Razorpay failure', [
                    'user_id'    => $user->id,
                    'is_trial'   => $isTrial,
                    'is_upgrade' => $isUpgradeFromTrial,
                ]);
            }
        }
    }

    /**
     * Attempt to cancel the existing trial Razorpay subscription when a user upgrades.
     * Failure is logged prominently but does NOT block the upgrade — the team
     * can manually cancel the Razorpay subscription if the API call fails.
     */
    private function cancelExistingTrialRazorpaySubscription(User $user, string $keyId, string $keySecret): void
    {
        $trialTx = BillingTransaction::where('user_id', $user->id)
            ->whereJsonContains('metadata->is_trial', true)
            ->latest()
            ->first();

        if (! $trialTx?->provider_subscription_id) {
            Log::warning('BillingController: no trial Razorpay subscription found to cancel during upgrade', [
                'user_id' => $user->id,
            ]);
            return;
        }

        $rzpSubId = $trialTx->provider_subscription_id;

        try {
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->post("https://api.razorpay.com/v1/subscriptions/{$rzpSubId}/cancel", [
                    'cancel_at_cycle_end' => 0,
                ]);

            if ($response->successful()) {
                Log::info('BillingController: trial Razorpay subscription cancelled during upgrade', [
                    'user_id'    => $user->id,
                    'rzp_sub_id' => $rzpSubId,
                ]);
            } else {
                Log::error('BillingController: MANUAL ACTION REQUIRED — failed to cancel trial Razorpay subscription during upgrade', [
                    'user_id'    => $user->id,
                    'rzp_sub_id' => $rzpSubId,
                    'status'     => $response->status(),
                    'body'       => $response->json(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('BillingController: MANUAL ACTION REQUIRED — exception cancelling trial Razorpay subscription during upgrade', [
                'user_id'    => $user->id,
                'rzp_sub_id' => $rzpSubId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}