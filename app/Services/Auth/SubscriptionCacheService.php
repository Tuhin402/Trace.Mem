<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Cache\TraceMemCache;

/**
 * SubscriptionCacheService — single invalidation coordinator for subscription
 * and API key state changes.
 *
 * This service is the bridge between SubscriptionEntitlementService (which
 * resolves entitlements from the DB) and TraceMemCache (which owns the cache
 * operations). Controllers and other services call this service to:
 *   - Read cached entitlements (via rememberEntitlements)
 *   - Invalidate entitlements cache (on key revoke / plan change)
 *   - Invalidate analytics cache (on any data-changing event)
 */
class SubscriptionCacheService
{
    public function __construct(
        private readonly TraceMemCache $cache,
        private readonly SubscriptionEntitlementService $entitlements,
    ) {}

    /**
     * Get entitlements for a user — reads through TraceMemCache.
     * If cache miss, resolves from database via SubscriptionEntitlementService.
     */
    public function getEntitlements(User $user): array
    {
        return $this->cache->rememberEntitlements(
            $user,
            fn () => $this->entitlements->resolveFromDatabase($user)
        );
    }

    /**
     * Invalidate the entitlements cache for a specific user.
     * Call after: API key revoke, API key rotate, subscription cancel/activate.
     */
    public function forgetEntitlements(User $user): void
    {
        $this->cache->forgetEntitlements($user);
    }

    /**
     * Invalidate the analytics cache for a specific user.
     * Call after: API key create/revoke/rotate, subscription cancel, webhook processed.
     */
    public function forgetUserAnalytics(User $user): void
    {
        $this->cache->forgetUserAnalytics($user);
    }
}
