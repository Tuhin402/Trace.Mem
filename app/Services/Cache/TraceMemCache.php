<?php

namespace App\Services\Cache;

use App\Models\User;
use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TraceMemCache — the single owner of all application cache operations.
 *
 * Business services NEVER call Cache::tags() directly. This class handles:
 *   - Driver capability detection (Redis tagging vs. file/database fallback)
 *   - Cache versioning for O(1) global schema invalidation
 *   - Per-entity invalidation via tags (Redis) or explicit forgets (fallback)
 *   - Consistent TTLs and key namespacing
 *
 * Redis path:    Cache::tags([...])->remember(key, ttl, cb)
 * Fallback path: Cache::remember(key, ttl, cb) + Cache::forget(key)
 */
class TraceMemCache
{
    // ── TTL constants ─────────────────────────────────────────────────────
    const ENTITLEMENTS_TTL = 600;   // 10 min
    const PLAN_TTL         = 3600;  // 60 min
    const PRICING_TTL      = 3600;  // 60 min
    const ANALYTICS_TTL    = 300;   // 5 min

    // ── Version key (stored in Redis / fallback cache) ────────────────────
    private string $versionKey = 'tracemem:cache_version';

    // ── Known analytics periods (for fallback invalidation) ───────────────
    private array $analyticsPeriods = [
        'all_time', 'today', '7_days', '30_days', '90_days',
        'this_month', 'last_month', 'year_to_date',
    ];

    // =========================================================================
    // Driver detection
    // =========================================================================

    private function supportsTagging(): bool
    {
        try {
            return Cache::getStore() instanceof TaggableStore;
        } catch (\Throwable) {
            return false;
        }
    }

    // =========================================================================
    // Cache versioning — O(1) global schema invalidation
    // =========================================================================

    public function version(): int
    {
        try {
            return (int) Cache::get($this->versionKey, 1);
        } catch (\Throwable) {
            return 1;
        }
    }

    /**
     * Bump the global cache version.
     * All keys built with the old version become unreachable immediately.
     * Keys expire naturally via their TTLs — no key scan required.
     */
    public function bumpVersion(): void
    {
        try {
            Cache::increment($this->versionKey);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: failed to bump version', ['error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // Key builder
    // =========================================================================

    private function key(string $base): string
    {
        return "v{$this->version()}:tracemem:{$base}";
    }

    // =========================================================================
    // Entitlements cache
    // =========================================================================

    /**
     * Remember entitlements for a user.
     * Tags: ['user:{id}', 'entitlements'] (Redis) / namespaced key (fallback)
     */
    public function rememberEntitlements(User $user, Closure $callback): array
    {
        $key = $this->key("entitlements:{$user->id}");

        try {
            if ($this->supportsTagging()) {
                return Cache::tags(["user:{$user->id}", 'entitlements'])
                    ->remember($key, self::ENTITLEMENTS_TTL, $callback);
            }

            return Cache::remember($key, self::ENTITLEMENTS_TTL, $callback);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: entitlements cache miss, falling back to DB', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    /**
     * Invalidate entitlements cache for a specific user.
     */
    public function forgetEntitlements(User $user): void
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags(["user:{$user->id}", 'entitlements'])->flush();
                return;
            }

            Cache::forget($this->key("entitlements:{$user->id}"));
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: failed to forget entitlements', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Plans + Pricing cache
    // =========================================================================

    /**
     * Remember plan features for a specific plan ID.
     * Tags: ['plans', 'plan:{id}'] (Redis) / namespaced key (fallback)
     */
    public function rememberPlanFeatures(int $planId, Closure $callback): mixed
    {
        $key = $this->key("plan:{$planId}");

        try {
            if ($this->supportsTagging()) {
                return Cache::tags(['plans', "plan:{$planId}"])
                    ->remember($key, self::PLAN_TTL, $callback);
            }

            return Cache::remember($key, self::PLAN_TTL, $callback);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: plan features cache miss, falling back to DB', [
                'plan_id' => $planId,
                'error'   => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    /**
     * Remember the public pricing catalogue (all active plans).
     * Tags: ['plans'] (Redis) / namespaced key (fallback)
     */
    public function rememberPricing(Closure $callback): mixed
    {
        $key = $this->key('pricing:public');

        try {
            if ($this->supportsTagging()) {
                return Cache::tags(['plans'])
                    ->remember($key, self::PRICING_TTL, $callback);
            }

            return Cache::remember($key, self::PRICING_TTL, $callback);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: pricing cache miss, falling back to DB', [
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    /**
     * Invalidate all plan + pricing caches.
     */
    public function forgetAllPlanData(): void
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags(['plans'])->flush();
                return;
            }

            // Fallback: forget the pricing key; individual plan keys expire by TTL
            Cache::forget($this->key('pricing:public'));
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: failed to forget plan data', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Analytics cache
    // =========================================================================

    /**
     * Remember analytics data for a user + period.
     * Tags: ['user:{id}', 'analytics'] (Redis) / namespaced key (fallback)
     */
    public function rememberAnalytics(User $user, string $period, Closure $callback): array
    {
        $key = $this->key("analytics:{$user->id}:{$period}");

        try {
            if ($this->supportsTagging()) {
                return Cache::tags(["user:{$user->id}", 'analytics'])
                    ->remember($key, self::ANALYTICS_TTL, $callback);
            }

            return Cache::remember($key, self::ANALYTICS_TTL, $callback);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: analytics cache miss, falling back to DB', [
                'user_id' => $user->id,
                'period'  => $period,
                'error'   => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    /**
     * Forced write: store pre-computed analytics data into cache.
     * Used by AggregateUsageStatsJob after it recomputes from DB.
     */
    public function putAnalytics(User $user, string $period, array $data): void
    {
        $key = $this->key("analytics:{$user->id}:{$period}");

        try {
            if ($this->supportsTagging()) {
                Cache::tags(["user:{$user->id}", 'analytics'])
                    ->put($key, $data, self::ANALYTICS_TTL);
                return;
            }

            Cache::put($key, $data, self::ANALYTICS_TTL);
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: failed to put analytics', [
                'user_id' => $user->id,
                'period'  => $period,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate all analytics caches for a specific user.
     */
    public function forgetUserAnalytics(User $user): void
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags(["user:{$user->id}", 'analytics'])->flush();
                return;
            }

            // Fallback: forget each known period key individually
            foreach ($this->analyticsPeriods as $period) {
                Cache::forget($this->key("analytics:{$user->id}:{$period}"));
            }
        } catch (\Throwable $e) {
            Log::warning('TraceMemCache: failed to forget user analytics', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
