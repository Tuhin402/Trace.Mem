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
     *
     * We cache plain arrays (via ->toArray()) rather than Eloquent models.
     * phpredis uses PHP's native serialize(), which produces __PHP_Incomplete_Class
     * when a new process deserializes an object whose class hasn't been autoloaded yet.
     * Arrays are always safely portable across process restarts and serializers.
     */
    public function activePlans(): Collection
    {
        $rows = $this->cache->rememberPricing(fn () => SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('price_monthly')
            ->get()
            ->toArray()   // store as plain array — serializer-safe
        );

        // Wrap back into a Collection so callers and return type are unaffected.
        return collect($rows);
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