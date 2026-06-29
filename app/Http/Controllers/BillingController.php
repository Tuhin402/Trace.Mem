<?php

namespace App\Http\Controllers;

use App\Jobs\AggregateUsageStatsJob;
use App\Models\BillingTransaction;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Auth\SubscriptionCacheService;
use App\Services\Billing\BillingCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService $catalog,
        private readonly SubscriptionCacheService $subscriptionCache,
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

        return Inertia::render('app/Billing', [
            'plan'         => $plan,
            'plans'        => $this->catalog->activePlans(),
            'subscription' => $displaySub
                ? [
                    'id'                  => $displaySub->id,
                    'status'              => $displaySub->status,
                    'billing_cycle'       => $displaySub->billing_cycle,
                    'starts_at'           => $displaySub->starts_at?->format('M j, Y'),
                    'renews_at'           => $displaySub->renews_at?->format('M j, Y'),
                    'ends_at'             => $displaySub->ends_at?->format('M j, Y'),
                    'auto_renew'          => $displaySub->auto_renew,
                    'cancelled_at'        => $displaySub->cancelled_at?->format('M j, Y \a\t g:i A'),
                    'cancellation_reason' => $displaySub->cancellation_reason,
                    'is_cancelled'        => $displaySub->isCancelled(),
                ]
                : null,
            'usage'        => [
                'active_keys'    => $activeKeyCount,
                'total_requests' => $totalRequests,
            ],
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
            'auto_renew'          => false,
        ]);

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
     *  Flow:
     *    1. Resolve / lazily create a Razorpay Plan for this plan+cycle
     *    2. Create a Razorpay Subscription for the user
     *    3. Persist a pending BillingTransaction
     *    4. Return JSON to the frontend for Razorpay modal initialisation
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

        // All Razorpay amounts are in the smallest currency unit (paise = INR × 100)
        $amountPaise = (int) round(((float) $amount) * 100);

        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');

        // ── Step 1: Resolve or lazily create a Razorpay Plan ──────────────
        $razorpayPlanIds = (array) ($plan->razorpay_plan_ids ?? []);
        $razorpayPlanId  = $razorpayPlanIds[$cycle] ?? null;

        if (! $razorpayPlanId) {
            // period + interval mapping for Razorpay Plans API
            [$rzpPeriod, $rzpInterval] = match ($cycle) {
                'monthly'   => ['monthly', 1],
                'quarterly' => ['monthly', 3],  // Razorpay has no 'quarterly'; use monthly×3
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
                    'user_id'  => $request->user()->id,
                    'plan_id'  => $plan->id,
                    'cycle'    => $cycle,
                    'status'   => $planResponse->status(),
                    'body'     => $planResponse->json(),
                ]);

                return response()->json([
                    'error' => 'Payment provider is unavailable. Please try again shortly.',
                ], 502);
            }

            $razorpayPlanId            = $planResponse->json('id');
            $razorpayPlanIds[$cycle]   = $razorpayPlanId;
            $plan->forceFill(['razorpay_plan_ids' => $razorpayPlanIds])->save();

            Log::info('BillingController::checkout — Razorpay plan created and cached', [
                'rzp_plan_id'  => $razorpayPlanId,
                'plan_slug'    => $plan->slug,
                'billing_cycle' => $cycle,
            ]);
        }

        // ── Step 2: Create Razorpay Subscription ──────────────────────────
        // total_count sets the maximum number of billing cycles.
        // Use large values to approximate an "unlimited" subscription.
        $totalCount = match ($cycle) {
            'monthly'   => 120,  // 10 years
            'quarterly' => 40,   // ~10 years
            'yearly'    => 10,   // 10 years
        };

        $user = $request->user();

        $subscriptionPayload = [
            'plan_id'         => $razorpayPlanId,
            'total_count'     => $totalCount,
            'quantity'        => 1,
            'customer_notify' => 0, // TraceMem owns email notifications
            'notes'           => [
                'user_id'       => (string) $user->id,
                'plan_id'       => (string) $plan->id,
                'billing_cycle' => $cycle,
            ],
        ];

        $subscriptionResponse = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/subscriptions', $subscriptionPayload);

        if (! $subscriptionResponse->successful()) {
            Log::error('BillingController::checkout — Razorpay subscription creation failed', [
                'user_id'       => $user->id,
                'rzp_plan_id'   => $razorpayPlanId,
                'status'        => $subscriptionResponse->status(),
                'body'          => $subscriptionResponse->json(),
            ]);

            return response()->json([
                'error' => 'Payment provider is unavailable. Please try again shortly.',
            ], 502);
        }

        $rzpSubscription = $subscriptionResponse->json();

        // ── Step 3: Persist pending BillingTransaction ────────────────────
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
        ]);

        Log::info('BillingController::checkout — pending transaction created', [
            'user_id'              => $user->id,
            'rzp_subscription_id'  => $rzpSubscription['id'],
            'plan_slug'            => $plan->slug,
            'billing_cycle'        => $cycle,
            'amount_paise'         => $amountPaise,
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
        ]);
    }

    /* ════════════════════════════════════════════════════════════
     *  VERIFY PAYMENT — server-side signature verification
     *
     *  Called by the frontend Razorpay modal handler callback.
     *  Verifies the HMAC-SHA256 signature and activates the
     *  subscription in an atomic DB transaction.
     *
     *  Never trusts frontend success alone — only the verified
     *  server-side signature is authoritative.
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

        DB::transaction(function () use ($user, $data, &$planId, &$subPlanId, &$cycle) {
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

            // Mark transaction paid and record payment ID
            $tx->update([
                'status'                     => 'paid',
                'provider_payment_intent_id' => $data['razorpay_payment_id'],
                'raw_payload'                => array_merge((array) ($tx->raw_payload ?? []), [
                    'razorpay_payment_id'  => $data['razorpay_payment_id'],
                    'razorpay_signature'   => $data['razorpay_signature'],
                    'verified_at'          => now()->toIso8601String(),
                ]),
            ]);

            // Activate the UserSubscription (idempotent via updateOrCreate)
            // renews_at will be enriched by the subscription.charged / subscription.activated webhook
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
                    'renews_at'       => null,
                    'ends_at'         => null,
                    'auto_renew'      => true,
                    'overage_enabled' => false,
                    'quotas_snapshot' => [],
                ]
            );

            // Link the transaction to the newly created/found subscription
            $tx->update(['user_subscription_id' => $userSub->id]);
        });

        // Invalidate caches so entitlements and dashboard data are refreshed immediately
        $this->subscriptionCache->forgetEntitlements($user);
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        Log::info('BillingController::verifyPayment — subscription activated', [
            'user_id'         => $user->id,
            'subscription_id' => $data['razorpay_subscription_id'],
            'payment_id'      => $data['razorpay_payment_id'],
            'plan_id'         => $subPlanId,
            'billing_cycle'   => $cycle,
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
}