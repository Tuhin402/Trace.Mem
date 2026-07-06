<?php

namespace App\Services\Memory\Decision;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Fire-and-forget Redis telemetry counters for the MemoryDecisionEngine.
 *
 * Every decision increments a set of Redis counters that power future
 * analytics dashboards. All operations are wrapped in try/catch — a Redis
 * blip NEVER affects the decision or the API response.
 *
 * Key schema (flat, scannable)
 * ────────────────────────────
 *   memory.decision.remember              — total store decisions
 *   memory.decision.skip                  — total skip decisions
 *   memory.rule.{rule_id}                 — per-rule match count
 *   memory.skip.{reason_code}             — per-skip reason count
 *   memory.near_miss                      — near-miss count
 *   memory.engine.v{N}.rule.v{M}          — decisions per engine+rule version pair
 *
 * Dashboard query examples
 * ────────────────────────
 *   SCAN memory.rule.*  → sort by value → "top remembered rule types"
 *   GET  memory.decision.remember         → total memories stored via /chat
 *   GET  memory.near_miss                 → tuning signal for threshold
 *
 * Telemetry is disabled when MDE_TELEMETRY=false (feature flag).
 */
class DecisionTelemetry
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('memory_rules.feature_flags.enable_telemetry', true);
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Record a completed decision. Call after every decide() invocation.
     *
     * @param string[] $matchedRuleIds
     */
    public function record(
        bool   $remember,
        string $reasonCode,
        array  $matchedRuleIds,
        int    $engineVersion,
        int    $ruleVersion,
        bool   $nearMiss = false
    ): void {
        if (! $this->enabled) {
            return;
        }

        try {
            // Overall decision counters
            if ($remember) {
                $this->increment('memory.decision.remember');
            } else {
                $this->increment('memory.decision.skip');
                $this->increment('memory.skip.' . $reasonCode);
            }

            // Per-rule counters
            foreach ($matchedRuleIds as $ruleId) {
                $this->increment('memory.rule.' . str_replace('.', '_', $ruleId));
            }

            // Near-miss counter
            if ($nearMiss) {
                $this->increment('memory.near_miss');
            }

            // Per engine+rule version pair
            $this->increment("memory.engine.v{$engineVersion}.rule.v{$ruleVersion}");

        } catch (Throwable) {
            // Non-critical — never fail the request over telemetry
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function increment(string $key): void
    {
        // Cache::increment is atomic and Redis-safe.
        // TTL: 90 days — long enough for monthly analysis without forever growth.
        $ttl = 60 * 60 * 24 * 90;

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $ttl);
        }
    }
}
