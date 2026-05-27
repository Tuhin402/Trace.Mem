<?php

namespace App\Http\Controllers;

use App\Services\Billing\ApiUsageAnalyticsService;
use App\Services\Billing\BillingCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BillingCatalogService $catalog,
        private readonly ApiUsageAnalyticsService $analytics,
    ) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $filters = $request->only(['period', 'month']);
        $usage   = $this->analytics->forUser($user, $filters);

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
            'selectedFilters' => $filters,
            'subscription'    => $subscription
                ? [
                    'id'           => $subscription->id,
                    'billing_cycle'=> $subscription->billing_cycle,
                    'starts_at'    => $subscription->starts_at?->format('M j, Y'),
                    'renews_at'    => $subscription->renews_at?->format('M j, Y'),
                    'ends_at'      => $subscription->ends_at?->format('M j, Y'),
                    'auto_renew'   => $subscription->auto_renew,
                    'is_cancelled' => $subscription->isCancelled(),
                ]
                : null,
            'flash'           => [
                'message'   => session('message'),
                'plain_key' => session('plain_key'),
                'error'     => session('error'),
            ],
        ]);
    }
}