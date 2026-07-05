<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Extracted health check logic — previously inlined in MemoryController::health().
 *
 * The NVIDIA NIM probe sends a real inference request (max_tokens=1) rather
 * than calling /models. This proves the application can actually generate
 * responses, not merely that the NVIDIA API is accepting connections.
 *
 * The response key stays "openai" to preserve the existing health check
 * response contract used by monitoring tools and the Status page.
 */
class HealthService
{
    public function check(): JsonResponse
    {
        try {
            $startedAt = cache()->get('app_started_at');
            if (! $startedAt) {
                cache()->forever('app_started_at', now()->toIsoString());
                $startedAt = cache()->get('app_started_at');
            }
        } catch (Throwable) {
            $startedAt = null; // Redis unreachable — uptime unavailable but health can still report
        }

        $t0     = microtime(true);
        $checks = [];

        // ── Database ──────────────────────────────────────────────────────────
        try {
            DB::connection()->getPdo();
            $checks['db'] = ['ok' => true];
        } catch (Throwable $e) {
            $checks['db'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // ── Redis ─────────────────────────────────────────────────────────────
        try {
            Redis::connection()->command('ping', []);
            $checks['redis'] = ['ok' => true];
        } catch (Throwable $e) {
            $checks['redis'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // ── Queue backend + worker heartbeat ──────────────────────────────────
        try {
            $driver = config('queue.default');

            if ($driver === 'redis') {
                Redis::connection()->command('ping', []);
                $heartbeat = Redis::connection()->get('queue:heartbeat');
                $checks['queue'] = [
                    'ok'                => (bool) $heartbeat,
                    'driver'            => 'redis',
                    'heartbeat_present' => (bool) $heartbeat,
                ];
            } elseif ($driver === 'database') {
                DB::table('jobs')->limit(1)->first();
                $checks['queue'] = ['ok' => true, 'driver' => 'database'];
            } else {
                $checks['queue'] = ['ok' => true, 'driver' => $driver, 'note' => 'backend not actively checked'];
            }
        } catch (Throwable $e) {
            $checks['queue'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // ── NVIDIA NIM — real inference probe ─────────────────────────────────
        // Sends an actual chat/completions request (max_tokens=1) to prove
        // the application can generate — not just that /models is reachable.
        // Response key stays "openai" to preserve the existing monitoring contract.
        try {
            $response = Http::withToken(config('services.nvidia_nim_openai.api_key'))
                ->acceptJson()
                ->timeout(5)
                ->post(config('services.nvidia_nim_openai.base_url') . '/chat/completions', [
                    'model'      => config('services.nvidia_nim_openai.model', 'openai/gpt-oss-20b'),
                    'messages'   => [['role' => 'user', 'content' => 'ping']],
                    'max_tokens' => 1,
                    'stream'     => false,
                ]);

            $checks['openai'] = [
                'ok'     => $response->successful(),
                'status' => $response->status(),
            ];
        } catch (Throwable $e) {
            $checks['openai'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        $latencyMs = (int) round((microtime(true) - $t0) * 1000);

        // Only DB is considered critical for overall health status
        $criticalChecks = ['db'];
        $ok = collect($criticalChecks)
            ->every(fn ($name) => ($checks[$name]['ok'] ?? false) === true);

        return response()->json([
            'ok'             => $ok,
            'service'        => 'memory-layer',
            'version'        => config('app.version'),
            'environment'    => app()->environment(),
            'uptime_seconds' => $startedAt ? now()->diffInSeconds($startedAt) : null,
            'latency_ms'     => $latencyMs,
            'timestamp'      => now()->toIso8601String(),
            'checks'         => $checks,
        ], $ok ? 200 : 503);
    }
}
