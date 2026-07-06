<?php

namespace App\Http\Controllers;

use App\Models\Memory;
use App\Services\Billing\ApiUsageAnalyticsService;
use App\Services\Billing\BillingCatalogService;
use App\Services\Billing\FreeTrialEligibilityService;
use App\Services\Cache\TraceMemCache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService $catalog,
        private readonly ApiUsageAnalyticsService $analytics,
        private readonly TraceMemCache $cache,
        private readonly FreeTrialEligibilityService $trialService,
    ) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $filters = $request->only(['period', 'month']);

        // ── Analytics read-through ─────────────────────────────────────────
        // Filtered requests (period/month params present): skip cache, hit DB directly.
        // Unfiltered requests: use cache. Cache is populated by AggregateUsageStatsJob
        // on data-changing events (key revoke/rotate/create, subscription cancel, webhook).
        //
        // Do NOT dispatch AggregateUsageStatsJob from here — analytics are refreshed
        // by state-change events, not by dashboard loads.
        $hasFilters = ! empty($filters['period']) || ! empty($filters['month']);

        if ($hasFilters) {
            // Filtered: bypass cache and query DB directly
            $usage = $this->analytics->forUser($user, $filters);
        } else {
            // Unfiltered: use cache with 'all_time' as the period key
            $period = 'all_time';
            $usage  = $this->cache->rememberAnalytics(
                $user,
                $period,
                fn () => $this->analytics->forUser($user, [])
            );
        }

        // Today's insights are always fresh (short TTL data, period='today')
        $todayInsights = $this->cache->rememberAnalytics(
            $user,
            'today',
            fn () => $this->analytics->insightsForUser($user, ['period' => 'today'])
        );

        $memories = Memory::where('user_id', $user->id)->latest('created_at')->take(6)->get();

        // Active subscription (null if cancelled / none)
        $subscription = $user?->currentSubscription;
        $plan         = $subscription?->subscriptionPlan;

        return Inertia::render('app/Dashboard', [
            'plan'            => $plan,
            // Only pass plans if user has no active plan — the UI uses this to show/hide pricing
            'plans'           => $plan ? [] : $this->catalog->activePlans(),
            'apiKeys'         => $user?->apiKeys()->latest()->get() ?? [],
            'usageStats'      => $usage['summary'],
            'usageLogs'       => $usage['recent'],
            'availableMonths' => $usage['months'],
            'todayInsights'   => $todayInsights,
            'memories'        => $memories,
            'selectedFilters' => $filters,
            'subscription'    => $subscription
                ? [
                    'id'            => $subscription->id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'starts_at'     => $subscription->starts_at?->format('M j, Y'),
                    'renews_at'     => $subscription->renews_at?->format('M j, Y'),
                    'ends_at'       => $subscription->ends_at?->format('M j, Y'),
                    'auto_renew'    => $subscription->auto_renew,
                    'is_cancelled'  => $subscription->isCancelled(),
                ]
                : null,
            'founding_offer'  => $this->trialService->getFoundingOfferPresentation($user),
            'flash'           => [
                'message'   => session('message'),
                'plain_key' => session('plain_key'),
                'error'     => session('error'),
            ],
        ]);
    }
}