<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache-backed circuit breaker for the NIM classifier.
 *
 * States
 * ──────
 *  CLOSED    — normal; NIM calls allowed
 *  OPEN      — NIM blocked; heuristic-only fallback active
 *  HALF_OPEN — recovery probe window; one NIM call allowed to test recovery
 *
 * Configuration
 * ─────────────
 *  CHAT_CIRCUIT_FAILURE_THRESHOLD  (default 5)  — consecutive failures to trip open
 *  CHAT_CIRCUIT_RECOVERY_SECONDS   (default 60) — seconds before half-open probe
 */
class CircuitBreaker
{
    private const PREFIX = 'chat:circuit:';
    private const TTL    = 7200; // 2-hour cache TTL for state keys

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Returns true if the circuit is OPEN (NIM calls should be skipped).
     * Automatically transitions OPEN → HALF_OPEN after the recovery window.
     */
    public function isOpen(): bool
    {
        try {
            $state = $this->getState();

            if ($state === 'open') {
                $openedAt        = (int) Cache::get(self::PREFIX . 'opened_at', 0);
                $recoverySeconds = (int) config('chat.circuit_recovery_seconds', 60);

                if (time() - $openedAt >= $recoverySeconds) {
                    // Transition to half-open: allow exactly one probe
                    Cache::put(self::PREFIX . 'state', 'half_open', self::TTL);
                    return false;
                }

                return true; // still within open window
            }

            // closed or half_open — allow call
            return false;
        } catch (Throwable) {
            // If cache is unreachable, allow the NIM call rather than blocking
            return false;
        }
    }

    /**
     * Call after a successful NIM response — resets the circuit to CLOSED.
     */
    public function recordSuccess(): void
    {
        try {
            Cache::put(self::PREFIX . 'state',    'closed', self::TTL);
            Cache::put(self::PREFIX . 'failures', 0,        self::TTL);
        } catch (Throwable) {
            // Non-critical; swallow
        }
    }

    /**
     * Call after a NIM failure (timeout, 5xx, exception).
     * Trips the circuit OPEN when the failure threshold is reached.
     */
    public function recordFailure(): void
    {
        try {
            $failures  = (int) Cache::get(self::PREFIX . 'failures', 0) + 1;
            $threshold = (int) config('chat.circuit_failure_threshold', 5);

            Cache::put(self::PREFIX . 'failures', $failures, self::TTL);

            if ($failures >= $threshold) {
                Cache::put(self::PREFIX . 'state',     'open', self::TTL);
                Cache::put(self::PREFIX . 'opened_at', time(), self::TTL);
            }
        } catch (Throwable) {
            // Non-critical; swallow
        }
    }

    /**
     * Returns the current state string: 'closed' | 'open' | 'half_open'.
     */
    public function getState(): string
    {
        try {
            return (string) Cache::get(self::PREFIX . 'state', 'closed');
        } catch (Throwable) {
            return 'closed';
        }
    }
}
