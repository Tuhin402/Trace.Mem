<?php

namespace App\Http\Controllers;

use App\Services\Chat\ChatOrchestrationService;
use App\Services\Memory\MemoryScopeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * ChatController — POST /api/v1/chat
 *
 * Responsibilities (controller only; no business logic here):
 *   1. Feature-flag guard — abort(503) when CHAT_ENDPOINT_ENABLED=false
 *   2. Chat-specific rate limit (separate from ApiKeyAuthMiddleware's per-key limit)
 *   3. Request validation
 *   4. Idempotency key resolution (Idempotency-Key header > idempotency_key body)
 *   5. Scope resolution via MemoryScopeResolver (shared with all existing endpoints)
 *   6. Delegate to ChatOrchestrationService
 *   7. Set X-Request-ID response header
 */
class ChatController extends Controller
{
    public function __construct(
        private readonly ChatOrchestrationService $orchestrator,
        private readonly MemoryScopeResolver      $scopeResolver,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        // ── 1. Feature flag ───────────────────────────────────────────────────
        if (! config('chat.enabled')) {
            return response()->json([
                'error'   => 'endpoint_disabled',
                'message' => 'The /chat endpoint is temporarily unavailable.',
            ], 503);
        }

        // ── 2. Chat-specific rate limiting ────────────────────────────────────
        /** @var \App\Models\ApiKey $apiKey */
        $apiKey  = $request->attributes->get('api_key');
        $rateKey = 'chat:rate:' . $apiKey->id;
        $rateMax = (int) config('chat.rate_limit_max', 2);
        $rateWin = (int) config('chat.rate_limit_window', 1);

        if (RateLimiter::tooManyAttempts($rateKey, $rateMax)) {
            $seconds = RateLimiter::availableIn($rateKey);

            return response()->json([
                'error'   => 'rate_limit_exceeded',
                'message' => "Chat rate limit exceeded. Try again in {$seconds} second(s).",
            ], 429);
        }

        RateLimiter::hit($rateKey, $rateWin);

        // ── 3. Validation ─────────────────────────────────────────────────────
        $data = $request->validate([
            'message'         => ['required', 'string', 'max:10000'],
            'memory_mode'     => ['sometimes', 'string', 'in:auto,force,off'],
            'context'         => ['sometimes', 'boolean'],
            'dry_run'         => ['sometimes', 'boolean'],
            'idempotency_key' => ['sometimes', 'string', 'max:128'],
            'debug'           => ['sometimes', 'boolean'],
        ]);

        // ── 4. Idempotency key (header takes precedence over body field) ──────
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? ($data['idempotency_key'] ?? null);

        // ── 5. Scope resolution ───────────────────────────────────────────────
        $scope = $this->scopeResolver->resolve($request);

        // ── 6. Orchestrate ────────────────────────────────────────────────────
        $result = $this->orchestrator->handle(
            tenantId:      $scope['tenant_id'],
            userId:        $scope['user_id'],
            apiKeyId:      $apiKey->id,
            message:       $data['message'],
            memoryMode:    $data['memory_mode'] ?? 'auto',
            includeContext: $data['context'] ?? true,
            dryRun:        $data['dry_run'] ?? false,
            debug:         $data['debug'] ?? false,
            idempotencyKey: $idempotencyKey,
        );

        // ── 7. Extract HTTP status + set X-Request-ID header ──────────────────
        $httpStatus = $result['__http_status'] ?? 200;
        unset($result['__http_status']);

        return response()
            ->json($result, $httpStatus)
            ->header('X-Request-ID', $result['request_id'] ?? '');
    }
}
