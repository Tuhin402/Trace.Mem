<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * SubscriptionEntitlementService — resolves plan-level entitlements for a user.
 *
 * resolveForUser() → delegates to SubscriptionCacheService (which reads through
 *                     TraceMemCache, falling back to resolveFromDatabase() on miss).
 *
 * resolveFromDatabase() → the raw DB query, called only by SubscriptionCacheService
 *                          on a cache miss.
 *
 * All other public methods (resolveModeFor, resolveRateLimitFor, etc.) remain
 * unchanged — they call resolveForUser() which goes through cache transparently.
 */
class SubscriptionEntitlementService
{
    // SubscriptionCacheService is injected lazily (set after construction) to
    // avoid a circular dependency: CacheService → EntitlementService → CacheService.
    // ApiKeyService also injects EntitlementService directly (for createForUser),
    // so we keep the constructor lean.
    private ?SubscriptionCacheService $cacheService = null;

    /**
     * Inject the cache coordinator. Called by the service container after
     * construction via SubscriptionCacheService itself.
     */
    public function setCacheService(SubscriptionCacheService $service): void
    {
        $this->cacheService = $service;
    }

    /**
     * Resolve entitlements — goes through cache if available, otherwise hits DB.
     */
    public function resolveForUser(User $user): array
    {
        if ($this->cacheService !== null) {
            return $this->cacheService->getEntitlements($user);
        }

        // Fallback: no cache service injected (e.g., during unit tests or
        // internal calls from ApiKeyService before cache is wired up)
        return $this->resolveFromDatabase($user);
    }

    /**
     * Raw database resolution — no caching.
     * Called by SubscriptionCacheService on a cache miss.
     */
    public function resolveFromDatabase(User $user): array
    {
        $subscription = \App\Models\UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNull('cancelled_at')
            ->with(['subscriptionPlan.features'])
            ->latest('starts_at')
            ->first();

        $plan = $subscription?->subscriptionPlan;

        return [
            'subscription'                    => $subscription,
            'plan'                            => $plan,
            'has_active_subscription'         => (bool) $subscription,
            'base_mode'                       => $plan?->base_mode ?? 'semantic_only',
            'allow_test_keys'                 => (bool) ($plan?->allow_test_keys ?? true),
            'allow_live_keys'                 => (bool) ($plan?->allow_live_keys ?? false),
            'memory_write_limit'              => (int) ($plan?->memory_write_limit ?? 200),
            'request_limit'                   => (int) ($plan?->request_limit ?? 1000),
            'api_key_limit'                   => (int) ($plan?->api_key_limit ?? 1),
            'test_api_key_limit'              => (int) ($plan?->test_api_key_limit ?? $plan?->api_key_limit ?? 1),
            'live_api_key_limit'              => (int) ($plan?->live_api_key_limit ?? $plan?->api_key_limit ?? 1),
            'test_key_ttl_days'               => (int) ($plan?->test_key_ttl_days ?? 30),
            'live_key_ttl_days'               => $plan?->live_key_ttl_days !== null ? (int) $plan->live_key_ttl_days : null,
            'request_rate_limit_max_requests' => (int) ($plan?->request_rate_limit_max_requests ?? 1),
            'request_rate_limit_window_seconds' => (int) ($plan?->request_rate_limit_window_seconds ?? 30),
            'test_rate_limit_max_requests'    => (int) ($plan?->test_rate_limit_max_requests ?? 1),
            'test_rate_limit_window_seconds'  => (int) ($plan?->test_rate_limit_window_seconds ?? 20),
        ];
    }

    public function resolveModeFor(User $user, string $environment): string
    {
        if ($environment === 'test') {
            return 'semantic_only';
        }

        $plan = $this->resolveForUser($user)['plan'];

        return $plan?->base_mode ?? 'semantic_only';
    }

    public function resolveRateLimitFor(User $user, string $environment): array
    {
        $info = $this->resolveForUser($user);

        if ($environment === 'test') {
            return [
                'max_requests'   => $info['test_rate_limit_max_requests'],
                'window_seconds' => $info['test_rate_limit_window_seconds'],
            ];
        }

        return [
            'max_requests'   => $info['request_rate_limit_max_requests'],
            'window_seconds' => $info['request_rate_limit_window_seconds'],
        ];
    }

    public function isTestEnvironmentAllowed(User $user): bool
    {
        return $this->resolveForUser($user)['allow_test_keys'];
    }

    public function isLiveEnvironmentAllowed(User $user): bool
    {
        return $this->resolveForUser($user)['allow_live_keys'];
    }
}