<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use App\Models\User;
use App\Services\Auth\ApiKeyService;
use App\Services\Auth\SubscriptionEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiKeyAuthMiddleware
{
    public function __construct(
        private readonly ApiKeyService                  $apiKeys,
        private readonly SubscriptionEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plainKey = $request->bearerToken();

        if (blank($plainKey)) {
            return response()->json(['message' => 'Missing API key.'], 401);
        }

        $apiKey = $this->apiKeys->findByPlainKey($plainKey);

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        if ($apiKey->isRevoked() || $apiKey->isExpired()) {
            return response()->json(['message' => 'API key is revoked or expired.'], 401);
        }

        // ── Subscription gate ─────────────────────────────────────────────────
        // Sandbox (test) keys are restricted to localhost/Postman already;
        // skip the billing check so local dev never breaks on a lapsed sub.
        // Live keys must belong to a user with an active, non-cancelled subscription.
        //
        // Failure mode: fail-OPEN on any exception (cache blip, DB timeout) so a
        // transient infrastructure hiccup never locks a paying user out of production.
        if ($apiKey->isLive()) {
            $subscriptionDenied = $this->checkSubscription($apiKey->user_id, $request);
            if ($subscriptionDenied !== null) {
                return $subscriptionDenied;
            }
        }

        if (! $this->requestMatchesPolicy($request, $apiKey)) {
            $host      = $request->getHost();
            $origin    = $request->headers->get('Origin') ?: $request->headers->get('Referer');
            $userAgent = strtolower($request->userAgent() ?? '');
            $isPostman = str_contains($userAgent, 'postman');
            $isHttps   = $request->isSecure();

            $this->logUsage($apiKey->id, $request, 403, microtime(true), [
                'reason' => 'sandbox_environment_violation',
            ]);

            if ($apiKey->isSandbox()) {
                return response()->json([
                    'error'   => 'sandbox_key_not_allowed_in_production',
                    'message' => 'This is a test (sandbox) key. It can only be used from localhost or Postman, not from a production or remote environment.',
                    'detail'  => [
                        'key_type'         => 'test',
                        'detected_origin'  => $origin ?? 'none (server-to-server)',
                        'detected_host'    => $host,
                        'is_https'         => $isHttps,
                        'allowed_in'       => ['localhost', '127.0.0.1', '*.local', '*.test', 'Postman'],
                    ],
                    'fix'     => 'Generate a live key from your TraceMem dashboard and use it in production. Test keys are strictly for local development and Postman testing.',
                ], 403);
            }

            return response()->json([
                'error'   => 'live_key_origin_not_allowed',
                'message' => 'This live key is not permitted to be used from the detected origin or over an insecure (HTTP) connection.',
                'detail'  => [
                    'key_type'        => 'live',
                    'detected_origin' => $origin ?? 'none',
                    'detected_host'   => $host,
                    'is_https'        => $isHttps,
                ],
                'fix'     => $isHttps
                    ? 'The origin "' . ($origin ?? $host) . '" is not in this key\'s allowed_origins list. Add it in your TraceMem dashboard under API Key settings.'
                    : 'Live keys require HTTPS. Your request was made over plain HTTP. Use HTTPS in production.',
            ], 403);
        }

        $rateKey = 'apikey:' . $apiKey->id . ':' . sha1($request->method() . '|' . $request->path());

        if (RateLimiter::tooManyAttempts($rateKey, (int) $apiKey->rate_limit_max_requests)) {
            $seconds = RateLimiter::availableIn($rateKey);

            $this->logUsage($apiKey->id, $request, 429, 0, [
                'reason' => 'rate_limited',
            ]);

            return response()->json([
                'message' => "Hold up. Try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($rateKey, (int) $apiKey->rate_limit_window_seconds);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('resolved_scope', [
            'tenant_id'    => $apiKey->tenant_scope_id,
            // 'user_id'      => $apiKey->user_id,
            'workspace_id' => $apiKey->workspace_id,   // nullable — backfilled for all existing keys
            'environment'  => $apiKey->environment,    // 'test' | 'live'
        ]);
        // $request->attributes->set('memory_mode', $apiKey->isTest() ? 'semantic_only' : $apiKey->mode);
        $request->attributes->set('memory_mode', $apiKey->isSandbox() ? 'semantic_only' : $apiKey->mode);
        $startedAt = microtime(true);

        try {
            $response = $next($request);
            $status = $response->getStatusCode();
            $this->logUsage($apiKey->id, $request, $status, $startedAt);
            $this->apiKeys->touchUsage($apiKey);

            return $response;
        } catch (Throwable $e) {
            $this->logUsage($apiKey->id, $request, 500, $startedAt, [
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function requestMatchesPolicy(Request $request, $apiKey): bool
    {
        $host      = $request->getHost();
        $origin    = $request->headers->get('Origin') ?: $request->headers->get('Referer');
        $userAgent = strtolower($request->userAgent() ?? '');
        $isPostman = str_contains($userAgent, 'postman');
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test');

        // ── Sandbox / test key enforcement ───────────────────────────────────
        // Test keys are STRICTLY limited to localhost and Postman.
        // They must NEVER work in any real production environment, regardless
        // of CORS settings on the caller's side. If the request comes from:
        //   - an HTTPS origin (production browser app)
        //   - a non-local HTTP origin
        //   - a server-to-server call that is not localhost
        // …the key is rejected. The developer must use a live key in production.
        if ($apiKey->isSandbox()) {
            return $isPostman || $isLocalHost;
        }

        // ── Live key enforcement ──────────────────────────────────────────────
        // Live keys require HTTPS (unless localhost/Postman for developer convenience).
        // If an explicit allowed_origins list exists, the origin must be in it.
        if ($origin && ! $this->originAllowed($origin, $apiKey)) {
            return false;
        }

        if (! $request->isSecure() && ! $isPostman && ! $isLocalHost) {
            return false;
        }

        return true;
    }

    private function originAllowed(string $origin, $apiKey): bool
    {
        $allowedOrigins = $apiKey->allowed_origins ?? [];

        if (empty($allowedOrigins)) {
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST) ?: $origin;

        return in_array($origin, $allowedOrigins, true)
            || in_array($originHost, $allowedOrigins, true);
    }

    // private function rateLimitKey(ApiKey $apiKey, Request $request): string
    // {
    //     return 'apikey:' . $apiKey->id . ':' . sha1(
    //         $request->method() . '|' . $request->path()
    //     );
    // }

    private function logUsage(
        int $apiKeyId,
        Request $request,
        int $statusCode,
        float $startedAt,
        array $metadata = []
    ): void {
        $latencyMs = max(0, (int) round((microtime(true) - $startedAt) * 1000));
        $host = $request->getHost();
        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        ApiUsageLog::create([
            'api_key_id' => $apiKeyId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $statusCode,
            'latency_ms' => $latencyMs,
            'tokens_used' => (int) ($request->attributes->get('tokens_used') ?? 0),
            'ip_address' => $request->ip(),
            'request_host' => $host,
            'request_origin' => $origin,
            'is_sandbox' => (bool) ($request->attributes->get('memory_mode') === 'semantic_only'),
            'is_localhost' => in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.local'),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->header('X-Request-Id', (string) Str::uuid()),
            'requested_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check that the key owner has an active, non-cancelled subscription.
     *
     * Returns a JsonResponse (HTTP 402) if the subscription is inactive,
     * or null if the request should proceed.
     *
     * Fail-open contract: any Throwable is caught, logged as a warning,
     * and null is returned so a cache/DB blip never denies a paying user.
     *
     * This method is only called for live-environment keys. Sandbox keys
     * are already restricted to localhost/Postman and do not require a
     * subscription check — keeping local development frictionless.
     */
    private function checkSubscription(int $userId, Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $user = User::find($userId);

            // Guard: key has no user row (data integrity issue — fail closed).
            if ($user === null) {
                Log::error('ApiKeyAuthMiddleware: live key has no associated user', [
                    'user_id' => $userId,
                    'ip'      => $request->ip(),
                    'path'    => $request->path(),
                ]);

                return response()->json([
                    'error'   => 'account_not_found',
                    'message' => 'The account associated with this API key could not be found.',
                ], 401);
            }

            $entitlements = $this->entitlements->resolveForUser($user);

            if (! $entitlements['has_active_subscription']) {
                Log::info('ApiKeyAuthMiddleware: live key blocked — inactive subscription', [
                    'user_id' => $userId,
                    'ip'      => $request->ip(),
                    'path'    => $request->path(),
                ]);

                return response()->json([
                    'error'   => 'subscription_inactive',
                    'message' => 'Your subscription is inactive or has been cancelled. '
                               . 'Please renew your plan to continue using the API.',
                ], 402);
            }

            return null; // subscription is active — proceed

        } catch (Throwable $e) {
            // Fail-open: a transient cache or DB error must not lock paying users out.
            Log::warning('ApiKeyAuthMiddleware: subscription check failed — failing open', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'path'    => $request->path(),
            ]);

            return null;
        }
    }
}
