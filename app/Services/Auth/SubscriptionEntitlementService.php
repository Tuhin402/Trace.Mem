<?php

namespace App\Services\Auth;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

class SubscriptionEntitlementService
{
    public function resolveForUser(User $user): array
    {
        $subscription = UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNull('cancelled_at')          
            ->with(['subscriptionPlan.features'])
            ->latest('starts_at')
            ->first();

        $plan = $subscription?->subscriptionPlan;


        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'has_active_subscription' => (bool) $subscription,
            'base_mode' => $plan?->base_mode ?? 'semantic_only',
            'allow_test_keys' => (bool) ($plan?->allow_test_keys ?? true),
            'allow_live_keys' => (bool) ($plan?->allow_live_keys ?? false),
            'memory_write_limit' => (int) ($plan?->memory_write_limit ?? 200),
            'request_limit' => (int) ($plan?->request_limit ?? 1000),
            'api_key_limit' => (int) ($plan?->api_key_limit ?? 1),
            'test_api_key_limit' => (int) ($plan?->test_api_key_limit ?? $plan?->api_key_limit ?? 1),
            'live_api_key_limit' => (int) ($plan?->live_api_key_limit ?? $plan?->api_key_limit ?? 1),
            'test_key_ttl_days' => (int) ($plan?->test_key_ttl_days ?? 30),
            'live_key_ttl_days' => $plan?->live_key_ttl_days !== null ? (int) $plan->live_key_ttl_days : null,
            'request_rate_limit_max_requests' => (int) ($plan?->request_rate_limit_max_requests ?? 1),
            'request_rate_limit_window_seconds' => (int) ($plan?->request_rate_limit_window_seconds ?? 30),
            'test_rate_limit_max_requests' => (int) ($plan?->test_rate_limit_max_requests ?? 1),
            'test_rate_limit_window_seconds' => (int) ($plan?->test_rate_limit_window_seconds ?? 20),
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
                'max_requests' => $info['test_rate_limit_max_requests'],
                'window_seconds' => $info['test_rate_limit_window_seconds'],
            ];
        }

        return [
            'max_requests' => $info['request_rate_limit_max_requests'],
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