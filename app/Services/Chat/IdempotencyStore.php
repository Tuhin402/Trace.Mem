<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache-backed idempotency store for POST /api/v1/chat.
 *
 * Cache key: chat:idempotency:{api_key_id}:{sha256(idempotency_key)}
 *
 * Keys are scoped to the API key so idempotency tokens cannot bleed
 * across tenants even if clients reuse the same string value.
 *
 * Cached response codes: 200, 502, 429.
 * TTL is controlled by CHAT_IDEMPOTENCY_TTL_SECONDS (default: 300 s).
 */
class IdempotencyStore
{
    // ── Public API ────────────────────────────────────────────────────────────

    public function has(int $apiKeyId, string $idempotencyKey): bool
    {
        try {
            return Cache::has($this->cacheKey($apiKeyId, $idempotencyKey));
        } catch (Throwable) {
            return false;
        }
    }

    /** Returns the cached response payload, or null if not found / cache error. */
    public function get(int $apiKeyId, string $idempotencyKey): ?array
    {
        try {
            $value = Cache::get($this->cacheKey($apiKeyId, $idempotencyKey));
            return is_array($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Stores the response payload.
     * Caches 200, 502, and 429 payloads so retried requests
     * (e.g. after client-side timeout) return the original result.
     */
    public function put(int $apiKeyId, string $idempotencyKey, array $response): void
    {
        try {
            Cache::put(
                $this->cacheKey($apiKeyId, $idempotencyKey),
                $response,
                (int) config('chat.idempotency_ttl', 300)
            );
        } catch (Throwable) {
            // Non-critical; idempotency is a best-effort safeguard
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function cacheKey(int $apiKeyId, string $idempotencyKey): string
    {
        return 'chat:idempotency:' . $apiKeyId . ':' . hash('sha256', $idempotencyKey);
    }
}
