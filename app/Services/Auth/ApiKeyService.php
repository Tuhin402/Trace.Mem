<?php

namespace App\Services\Auth;

use App\Models\ApiKey;
use App\Models\ApiKeyRotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function __construct(
        private readonly SubscriptionEntitlementService $entitlements
    ) {}

    public function createForUser(User $user, string $name, string $environment = 'test', array $options = [], ?ApiKey $replacing = null): array
    {
        return DB::transaction(function () use ($user, $name, $environment, $options, $replacing) {
            $policy = $this->entitlements->resolveForUser($user);
            $plan = $policy['plan'];
            $subscription = $policy['subscription'];
            

            if ($environment === 'test' && ! $policy['allow_test_keys']) {
                abort(403, 'This plan does not allow test keys.');
            }

            if ($environment === 'live') {
                if (! $policy['allow_live_keys']) {
                    abort(403, 'This plan does not allow live keys.');
                }
                if (! $policy['has_active_subscription']) {
                    abort(403, 'An active subscription is required to create a live key.');
                }
            }

            $limit = $environment === 'test' ? $policy['test_api_key_limit'] : $policy['live_api_key_limit'];

            $activeCount = $user->apiKeys()
            ->where('environment', $environment)
            ->whereNull('revoked_at')
            ->whereNull('cancelled_at') 
            ->when($replacing?->id, fn ($query) => $query->where('id', '!=', $replacing->id))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

            if ($activeCount >= $limit) {
                abort(403, "You have reached the {$environment} API key limit for your current plan.");
            }

            // $activeKeyCount = $this->activeKeyCountForUser($user);
            // if ($activeKeyCount >= (int) ($policy['api_key_limit'] ?? 0)) {
            //     abort(403, 'API key limit reached for your plan.');
            // }

            $plainKey = $this->generatePlainKey($environment);
            $hash = hash('sha256', $plainKey);

            $mode = $environment === 'test'
                ? 'semantic_only'
                : ($policy['base_mode'] ?? 'semantic_only');

            $expiresAt = $environment === 'test'
                ? now()->addDays($policy['test_key_ttl_days'])
                : ($subscription?->ends_at ?? (
                    $policy['live_key_ttl_days'] !== null
                        ? now()->addDays($policy['live_key_ttl_days'])
                        : null
                ));

            $scopes = $environment === 'test'
                ? ['memory:read', 'memory:write', 'memory:context', 'memory:debug']
                : ['memory:read', 'memory:write', 'memory:context'];

            $apiKey = ApiKey::create([
                'user_id' => $user->id,
                'tenant_scope_id' => $user->tenant_scope_id,
                'subscription_plan_id' => $plan?->id,
                'name' => $name,
                'key_prefix' => $environment === 'test' ? 'cmtest_' : 'cmlive_',
                'key_hash' => $hash,
                'key_last4' => substr($plainKey, -4),
                'environment' => $environment,
                'mode' => $mode,
                'sandbox_only' => $environment === 'test',
                'key_version' => 1,
                'issued_at' => now(),
                'expires_at' => $expiresAt,
                'rate_limit_max_requests' => $environment === 'test'
                    ? (int) ($policy['test_rate_limit_max_requests'] ?? 1)
                    : (int) ($policy['request_rate_limit_max_requests'] ?? 1),
                'rate_limit_window_seconds' => $environment === 'test'
                    ? (int) ($policy['test_rate_limit_window_seconds'] ?? 30)
                    : (int) ($policy['request_rate_limit_window_seconds'] ?? 30),
                'scopes' => $scopes,
                'metadata' => [
                    'source' => 'dashboard',
                    'plan_slug' => $plan?->slug,
                ],
                'allowed_origins' => $options['allowed_origins'] ?? null,
                'allowed_ips' => $options['allowed_ips'] ?? null,
            ]);

            return [
                'plain_key' => $plainKey,
                'api_key' => $apiKey,
            ];
        });
    }

    public function rotateForUser(User $user, ApiKey $apiKey, array $options = []): array
    {
        abort_unless($apiKey->user_id === $user->id, 403);

        $created = $this->createForUser(
            $user,
            $apiKey->name,
            $apiKey->environment,
            $options,
            $apiKey
        );

        $this->revoke($apiKey, 'rotated');

        ApiKeyRotation::create([
            'api_key_id' => $apiKey->id,
            'replaced_by_api_key_id' => $created['api_key']->id,
            'reason' => 'rotated',
            'metadata' => [
                'old_key_id' => $apiKey->id,
                'new_key_id' => $created['api_key']->id,
            ],
            'rotated_at' => now(),
        ]);

        return $created;
    }

    public function findByPlainKey(string $plainKey): ?ApiKey
    {
        return ApiKey::query()
            ->with('subscriptionPlan.features')
            ->where('key_hash', hash('sha256', $plainKey))
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function revoke(ApiKey $apiKey, string $reason = 'manual'): ApiKey
    {
        $apiKey->forceFill([
            'revoked_at' => now(),
            'metadata' => array_merge((array) $apiKey->metadata, [
                'revoked_reason' => $reason,
            ]),
        ])->save();

        return $apiKey->fresh();
    }

    public function touchUsage(ApiKey $apiKey): void
    {
        $apiKey->forceFill([
            'usage_count' => ((int) $apiKey->usage_count) + 1,
            'last_used_at' => now(),
        ])->save();
    }

    private function activeKeyCountForUser(User $user): int
    {
        return ApiKey::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    private function generatePlainKey(string $environment): string
    {
        return ($environment === 'test' ? 'cmtest_' : 'cmlive_') . Str::random(40);
    }
}