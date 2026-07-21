<?php

namespace App\Http\Controllers;

use App\Services\Memory\MemoryContextAssemblyService;
use App\Services\Memory\MemoryIngestionOrchestrator;
use App\Services\Memory\MemoryService;
use App\Services\Memory\MemorySemanticSegmentationService;
use App\Services\Memory\MemoryConflictService;
use App\Services\Memory\MemoryScopeResolver;
use App\Services\Memory\Decision\DecisionContext;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\MemoryExtractionService;
use App\Services\HealthService;

use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function __construct(
        private readonly MemoryIngestionOrchestrator $orchestrator,
        private readonly MemoryService $memoryService,
        private readonly MemoryContextAssemblyService $contextAssembler,
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
        private readonly MemoryConflictService $conflicts,
        private readonly MemoryScopeResolver $scopeResolver,
        private readonly HealthService $healthService,
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'string', 'in:recall_score,created_at,updated_at'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ]);

        $scope = $this->scopeResolver->resolve($request);

        $memories = $this->memoryService->recall(
            $scope['tenant_id'],
            $scope['user_id'],
            $data['limit'] ?? 5,
            $data['page'] ?? 1,
            $data['sort_by'] ?? 'recall_score',
            $data['sort_order'] ?? 'desc'
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
        return $this->healthService->check();
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

    public function debugExtract(Request $request, MemoryExtractionService $extractor)
    {
        $this->assertDebugScope($request);

        $data = $request->validate([
            'input' => ['required', 'string', 'max:10000'],
        ]);

        return response()->json([
            'extracted' => $extractor->extract($data['input']),
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

    /**
     * POST /api/v1/debug/memory-decision
     *
     * Internal explainability endpoint — gated by memory:debug API key scope.
     * Returns the full decision report including every rule evaluated, weights,
     * matched rules, confidence, reason code, and elapsed microseconds.
     *
     * No memory is stored. No side effects. Pure decision preview.
     *
     * Example response:
     * {
     *   "remember": true,
     *   "type": "fact",
     *   "confidence": 1.0,
     *   "reason_code": "IDENTITY_NAME_MATCH",
     *   "matched_rules": ["identity.name"],
     *   "weights": [100],
     *   "evaluated_rules": [
     *     {"id": "negative.roleplay", "matched": false, ...},
     *     {"id": "identity.name", "matched": true, "weight": 100, "terminal": true}
     *   ],
     *   "elapsed_us": 312
     * }
     */
    public function debugMemoryDecision(Request $request, MemoryDecisionEngine $engine)
    {
        $this->assertDebugScope($request);

        $data = $request->validate([
            'message'      => ['required', 'string', 'max:10000'],
            'memory_mode'  => ['sometimes', 'string', 'in:auto,force,off'],
        ]);

        $context = new DecisionContext(
            endpoint:   DecisionContext::ENDPOINT_CHAT,
            memoryMode: $data['memory_mode'] ?? 'auto',
            isDryRun:   true,
        );

        // trace=true forces full per-rule evaluation log
        $result = $engine->decide($data['message'], $context, trace: true);

        return response()->json($result->toDebugArray());
    } 
}