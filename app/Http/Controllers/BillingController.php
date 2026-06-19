<?php

namespace App\Http\Controllers;

use App\Jobs\AggregateUsageStatsJob;
use App\Models\BillingTransaction;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Auth\SubscriptionCacheService;
use App\Services\Billing\BillingCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\StripeClient;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService $catalog,
        private readonly SubscriptionCacheService $subscriptionCache,
    ) {}

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
        // Entitlements must be invalidated — user has lost their active subscription.
        // Analytics must be invalidated — subscription state changed.
        $this->subscriptionCache->forgetEntitlements($user);
        $this->subscriptionCache->forgetUserAnalytics($user);
        AggregateUsageStatsJob::dispatch($user->id, 'all_time')->onQueue('default');

        return redirect()
            ->route('billing.index')
            ->with('message', 'Your subscription has been cancelled. You will lose access to paid features immediately.');
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan_slug'     => ['required', 'string'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $amount = match ($data['billing_cycle']) {
            'monthly'   => $plan->price_monthly,
            'quarterly' => $plan->price_quarterly,
            'yearly'    => $plan->price_yearly,
        };

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode'        => 'subscription',
            'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('billing.cancel'),
            'customer_email' => $request->user()->email,
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                    'unit_amount' => (int) round(((float) $amount) * 100),
                    'recurring' => [
                        'interval' => 'month',
                        'interval_count' => match ($data['billing_cycle']) {
                            'monthly'   => 1,
                            'quarterly' => 3,
                            'yearly'    => 12,
                        },
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id'       => (string) $request->user()->id,
                'plan_id'       => (string) $plan->id,
                'billing_cycle' => $data['billing_cycle'],
            ],
        ]);

        BillingTransaction::create([
            'user_id'                      => $request->user()->id,
            'subscription_plan_id'         => $plan->id,
            'provider'                     => 'stripe',
            'provider_checkout_session_id' => $session->id,
            'billing_cycle'                => $data['billing_cycle'],
            'currency'                     => 'usd',
            'amount_total'                 => (int) round(((float) $amount) * 100),
            'status'                       => 'pending',
            'raw_payload'                  => ['checkout_session_id' => $session->id],
        ]);

        return redirect()->away($session->url);
    }

    public function success()
    {
        return redirect()->route('dashboard')->with('message', 'Payment completed. Your subscription will be activated shortly.');
    }

    public function cancel()
    {
        return redirect()->route('api.keys')->with('message', 'Checkout canceled.');
    }
}