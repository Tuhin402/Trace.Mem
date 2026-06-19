<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use App\Services\Cache\TraceMemCache;
use Illuminate\Support\Collection;

class BillingCatalogService
{
    public function __construct(
        private readonly TraceMemCache $cache,
    ) {}

    /**
     * Return all active subscription plans with their features.
     * Result is cached via TraceMemCache (tags: ['plans'] on Redis).
     */
    public function activePlans(): Collection
    {
        return $this->cache->rememberPricing(fn () => SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('price_monthly')
            ->get()
        );
    }

    /**
     * Invalidate all plan and pricing caches.
     * Call after admin changes plan configuration.
     */
    public function invalidatePlans(): void
    {
        $this->cache->forgetAllPlanData();
    }
}