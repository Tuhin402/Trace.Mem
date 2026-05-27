<?php

namespace App\Http\Controllers;

use App\Services\Memory\MemoryContextAssemblyService;
use App\Services\Memory\MemoryIngestionOrchestrator;
use App\Services\Memory\MemoryService;
use App\Services\Memory\MemorySemanticSegmentationService;
use App\Services\Memory\MemoryConflictService;
use App\Services\Memory\MemoryScopeResolver;
use App\Services\MemoryExtractionService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

class MemoryController extends Controller
{
    public function __construct(
        private readonly MemoryIngestionOrchestrator $orchestrator,
        private readonly MemoryService $memoryService,
        private readonly MemoryContextAssemblyService $contextAssembler,
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
        private readonly MemoryExtractionService $extractor,
        private readonly MemoryConflictService $conflicts,
        private readonly MemoryScopeResolver $scopeResolver,
    ) {}

    public function remember(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $scope = $this->scopeResolver->resolve($request);
        $mode = (string) $request->attributes->get('memory_mode', 'ai_first');

        $stored = $this->orchestrator->ingest(
            $scope['tenant_id'],
            $scope['user_id'],
            $data['content'],
            $mode
        );

        return response()->json([
            'message' => 'Memory saved',
            'memory' => $stored,
        ], 201);
    }

    public function recall(Request $request)
    {
        $data = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $scope = $this->scopeResolver->resolve($request);

        $memories = $this->memoryService->recall(
            $scope['tenant_id'],
            $scope['user_id'],
            $data['limit'] ?? 5
        );

        return response()->json([
            'memories' => $memories,
        ]);
    }

    public function assembleContext(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:10000'],
            'token_budget' => ['sometimes', 'integer', 'min:64', 'max:4000'],
            'candidate_limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'debug' => ['sometimes', 'boolean'],
        ]);

        $scope = $this->scopeResolver->resolve($request);

        $result = $this->contextAssembler->assemble(
            $scope['tenant_id'],
            $scope['user_id'],
            $data['query'],
            $data['token_budget'] ?? 600,
            $data['candidate_limit'] ?? 50,
            (bool) ($data['debug'] ?? false)
        );

        return response()->json($result);
    }

    public function health(Request $request)
    {
        $startedAt = cache()->get('app_started_at');
        if (! $startedAt) {
            cache()->forever('app_started_at', now()->toIso8601String());
            $startedAt = cache()->get('app_started_at');
        }

        $t0 = microtime(true);

        $checks = [];

        // DB
        try {
            DB::connection()->getPdo();
            $checks['db'] = ['ok' => true];
        } catch (\Throwable $e) {
            $checks['db'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Redis
        try {
            Redis::connection()->command('ping', []);
            $checks['redis'] = ['ok' => true];
        } catch (\Throwable $e) {
            $checks['redis'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Queue backend + worker heartbeat
        try {
            $driver = config('queue.default');

            if ($driver === 'redis') {
                Redis::connection()->command('ping', []);
                $heartbeat = Redis::connection()->get('queue:heartbeat');
                $checks['queue'] = [
                    'ok' => (bool) $heartbeat,
                    'driver' => 'redis',
                    'heartbeat_present' => (bool) $heartbeat,
                ];
            } elseif ($driver === 'database') {
                DB::table('jobs')->limit(1)->first();
                $checks['queue'] = ['ok' => true, 'driver' => 'database'];
            } else {
                $checks['queue'] = ['ok' => true, 'driver' => $driver, 'note' => 'backend not actively checked'];
            }
        } catch (\Throwable $e) {
            $checks['queue'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // OpenAI
        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(5)
                ->get('https://api.openai.com/v1/models');

            $checks['openai'] = [
                'ok' => $response->successful(),
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            $checks['openai'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        $latencyMs = (int) round((microtime(true) - $t0) * 1000);

        // $ok = collect($checks)->every(fn ($check) => ($check['ok'] ?? false) === true);
        $criticalChecks = ['db'];
        $ok = collect($criticalChecks)
            ->every(fn ($name) => ($checks[$name]['ok'] ?? false) === true);

        return response()->json([
            'ok' => $ok,
            'service' => 'memory-layer',
            'version' => config('app.version'),
            'environment' => app()->environment(),
            'uptime_seconds' => $startedAt ? now()->diffInSeconds($startedAt) : null,
            'latency_ms' => $latencyMs,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    // guard for debugs  
    private function assertDebugScope(Request $request): void
    {
        /** @var \App\Models\ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        abort_unless($apiKey && $apiKey->hasScope('memory:debug'), 403, 'Debug access is not allowed for this key.');
    }

    public function debugSemanticSegment(Request $request)
    {
        $this->assertDebugScope($request);

        $data = $request->validate([
            'input' => ['required', 'string', 'max:10000'],
        ]);

        return response()->json([
            'segments' => $this->semanticSegmenter->split($data['input']),
        ]);
    }

    public function debugExtract(Request $request)
    {
        $this->assertDebugScope($request);

        $data = $request->validate([
            'input' => ['required', 'string', 'max:10000'],
        ]);

        return response()->json([
            'extracted' => $this->extractor->extract($data['input']),
        ]);
    }

    public function debugConflicts(Request $request)
    {
        $this->assertDebugScope($request);
        
        $data = $request->validate([
            'content'   => ['required', 'string', 'max:10000'],
        ]);

        $scope = $this->scopeResolver->resolve($request);

        return response()->json([
            'conflicts' => $this->conflicts->preview(
                $scope['tenant_id'],
                $scope['user_id'],
                $data['content']
            ),
        ]);
    }
} 