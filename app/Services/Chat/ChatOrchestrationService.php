<?php

namespace App\Services\Chat;

use App\Services\Memory\Decision\DecisionContext;
use App\Services\Memory\Decision\DecisionResult;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\Memory\MemoryContextAssemblyService;
use App\Services\Memory\MemoryIngestionOrchestrator;
// use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates the full POST /api/v1/chat pipeline.
 *
 * This endpoint is a pure memory service — no LLM reply is generated.
 *
 * Execution order (non-dry-run):
 *   1. Idempotency check  → return cached response immediately if hit
 *   2. Decision           → MemoryDecisionEngine (deterministic, no AI, no HTTP)
 *   3. Memory ingestion   → MemoryIngestionOrchestrator (existing, unchanged)
 *   4. Context assembly   → MemoryContextAssemblyService (existing, unchanged)
 *   5. Build response
 *   6. Cache in idempotency store
 *   7. Log latency metrics (no user content)
 *
 * Failure isolation:
 *   Steps 2, 3, 4 are each wrapped independently. A failure in any one
 *   continues the pipeline (with degraded output) rather than aborting.
 *   Always returns HTTP 200 — no upstream LLM dependency.
 *
 * Memory infrastructure guarantee:
 *   All steps are fully operational with zero external HTTP calls.
 *   The MemoryDecisionEngine performs ZERO external calls.
 * 
 * One LLM call guaranteed:
 *   The decision engine adds zero LLM calls. The chat NIM call is always
 *   exactly one.
 */
class ChatOrchestrationService
{
    public function __construct(
        private readonly MemoryDecisionEngine         $decisionEngine,
        private readonly IdempotencyStore             $idempotencyStore,
        private readonly MemoryIngestionOrchestrator  $orchestrator,
        private readonly MemoryContextAssemblyService $contextAssembler,
        // private readonly PromptAssemblyService        $promptAssembler,
    ) {}

    // ── Public entry point ────────────────────────────────────────────────────

    /**
     * @param  string      $tenantId
     * @param  string      $userId
     * @param  int         $apiKeyId
     * @param  string      $message
     * @param  string      $memoryMode      auto | force | off
     * @param  bool        $includeContext  whether to assemble and inject context
     * @param  bool        $dryRun          preview only — no writes, no LLM
     * @param  bool        $debug           include debug block in response
     * @param  string|null $idempotencyKey  optional deduplication key
     * @return array       Response payload (may include __http_status for non-200)
     */
    public function handle(
        string  $tenantId,
        string  $userId,
        int     $apiKeyId,
        string  $message,
        string  $memoryMode      = 'auto',
        bool    $includeContext  = true,
        bool    $dryRun          = false,
        bool    $debug           = false,
        ?string $idempotencyKey  = null
    ): array {
        $requestId  = 'tm_chat_' . (string) Str::ulid();
        $totalStart = hrtime(true);

        // ── 1. Idempotency check ─────────────────────────────────────────────
        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyStore->get($apiKeyId, $idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // ── 2. Decision (deterministic, no AI) ───────────────────────────────
        $decisionContext = new DecisionContext(
            endpoint:   DecisionContext::ENDPOINT_CHAT,
            memoryMode: $memoryMode,
            isDryRun:   $dryRun,
        );

        $decisionStart   = hrtime(true);
        $decision        = $this->safeDecide($message, $decisionContext);
        $decisionMs      = $this->elapsedMs($decisionStart);
        $decisionWarning = $decision->via === 'decision_error' ? ($decision->reason) : null;

        // ── 2a. Dry-run: decision only, zero side effects ────────────────────
        if ($dryRun) {
            Log::info('chat.latency', [
                'request_id'   => $requestId,
                'prompt_version' => config('chat.prompt_version', 'v1'),
                'dry_run'      => true,
                'decision_via' => $decision->via,
                'decision_ms'  => $decisionMs,
                'total_ms'     => $decisionMs,
                'http_status'  => 200,
            ]);

            return [
                'request_id'     => $requestId,
                'dry_run'        => true,
                'would_remember' => $decision->remember,
                'type'           => $decision->type,
                'reason'         => $decision->reason,
                'reason_code'    => $decision->reasonCode,
                'via'            => $decision->via,
                'confidence'     => $decision->confidence,
                'matched_rules'  => $decision->matchedRules,
            ];
        }

        // ── 3. Memory ingestion ───────────────────────────────────────────────
        $memorySaved   = false;
        $memoryType    = null;
        $memoryWarning = null;
        $memoryMs      = 0;

        if ($decision->remember && $memoryMode !== 'off') {
            $memoryStart = hrtime(true);
            try {
                $this->orchestrator->ingest(
                    $tenantId,
                    $userId,
                    $message,
                    'ai_first',
                    $decision->toMemoryMetadata()
                );
                $memorySaved = true;
                $memoryType  = $decision->type;
            } catch (Throwable $e) {
                $memoryWarning = 'Memory storage failed; chat continues.';
            }
            $memoryMs = $this->elapsedMs($memoryStart);
        }

        // ── 4. Context assembly ───────────────────────────────────────────────
        $contextText     = '';
        $contextUsed     = false;
        $contextMemories = 0;
        $contextTokens   = 0;
        $candidateCount  = 0;
        $assembledFrom   = [];
        $contextWarning  = null;
        $contextMs       = 0;

        if ($includeContext) {
            $contextStart = hrtime(true);
            try {
                $ctxResult = $this->contextAssembler->assemble(
                    $tenantId,
                    $userId,
                    $message,
                    (int) config('chat.context_token_budget', 800),
                    50,
                    false
                );

                $contextText     = (string) ($ctxResult['context_text'] ?? '');
                $contextUsed     = $contextText !== '';
                $contextMemories = (int) ($ctxResult['selected_count'] ?? 0);
                $contextTokens   = (int) ($ctxResult['token_used'] ?? 0);
                $candidateCount  = (int) ($ctxResult['total_candidates'] ?? 0);
                $assembledFrom   = $this->extractTypesFromContextLines($ctxResult['context'] ?? []);
            } catch (Throwable $e) {
                $contextWarning = 'Context assembly failed; proceeding without context.';
            }
            $contextMs = $this->elapsedMs($contextStart);
        }

        
        // ── 5. Prompt assembly ────────────────────────────────────────────────
        // $systemPrompt = $this->promptAssembler->buildSystemPrompt($contextText, $contextUsed);
        // $messages     = $this->promptAssembler->buildMessages($systemPrompt, $message);

        // ── 6. NIM chat call (ONE call, 30 s timeout, no retry) ──────────────
        // $llmStart = hrtime(true);
        // try {
        //     $nimResponse = Http::withToken(config('services.nvidia_nim_openai.api_key'))
        //         ->acceptJson()
        //         ->timeout(30)
        //         ->post(config('services.nvidia_nim_openai.base_url') . '/chat/completions', [
        //             'model'       => config('services.nvidia_nim_openai.model', 'openai/gpt-oss-20b'),
        //             'messages'    => $messages,
        //             'temperature' => 0.7,
        //             'top_p'       => 1,
        //             'max_tokens'  => 1024,
        //             'stream'      => false,
        //         ]);

        //     $llmMs   = $this->elapsedMs($llmStart);
        //     $totalMs = $this->elapsedMs($totalStart);

        //     if (! $nimResponse->successful()) {
        //         return $this->fail502(
        //             $requestId, $decision, $memorySaved, $memoryType,
        //             $decisionMs, $memoryMs,
        //             $contextUsed, $contextMemories, $contextMs,
        //             $llmMs, $totalMs,
        //             $apiKeyId, $idempotencyKey
        //         );
        //     }

        //     $reply = (string) $nimResponse->json('choices.0.message.content', '');

        // } catch (Throwable $e) {
        //     $llmMs   = $this->elapsedMs($llmStart);
        //     $totalMs = $this->elapsedMs($totalStart);

        //     return $this->fail502(
        //         $requestId, $decision, $memorySaved, $memoryType,
        //         $decisionMs, $memoryMs,
        //         $contextUsed, $contextMemories, $contextMs,
        //         $llmMs, $totalMs,
        //         $apiKeyId, $idempotencyKey
        //     );
        // }

        $totalMs = $this->elapsedMs($totalStart);

        // ── 7. Build success response ─────────────────────────────────────────
        $response = [
            'request_id'    => $requestId,
            'memory'        => [
                'saved'       => $memorySaved,
                'type'        => $memoryType,
                'reason'      => $decision->reason,
                'reason_code' => $decision->reasonCode,
                'via'         => $decision->via,
            ],
            'context'       => [
                'used'            => $contextUsed,
                'candidate_count' => $candidateCount,
                'returned_count'  => $contextMemories,
                'token_budget'    => (int) config('chat.context_token_budget', 800),
                'tokens_used'     => $contextTokens,
                'assembled_from'  => $assembledFrom,
            ],
            'memory_engine' => [
                'provider'       => 'tracemem',
                'engine'         => 'rule_engine',
                'engine_version' => $decision->engineVersion,
                'rule_version'   => $decision->ruleVersion,
            ],
            'latency_ms'    => [
                'decision' => $decisionMs,
                'memory'   => $memoryMs,
                'context'  => $contextMs,
                // 'llm'      => $llmMs,
                'total'    => $totalMs,
            ],
        ];

        // debug block — only when explicitly requested
        if ($debug) {
            $warnings = array_values(array_filter([
                $decisionWarning,
                $memoryWarning,
                $contextWarning,
            ]));

            $response['debug'] = [
                'prompt_version'      => config('chat.prompt_version', 'v1'),
                'decision_confidence' => $decision->confidence,
                'decision_via'        => $decision->via,
                'matched_rules'       => $decision->matchedRules,
                'reason_code'         => $decision->reasonCode,
                'engine_version'      => $decision->engineVersion,
                'rule_version'        => $decision->ruleVersion,
                'context_segments'    => $contextText !== '' ? array_filter(explode("\n", trim($contextText))) : [],
                'warnings'            => $warnings,
            ];
        }

        // ── 8. Idempotency + latency log ──────────────────────────────────────
        if ($idempotencyKey !== null) {
            $this->idempotencyStore->put($apiKeyId, $idempotencyKey, $response);
        }

        Log::info('chat.latency', [
            'request_id'       => $requestId,
            // 'prompt_version'   => config('chat.prompt_version', 'v1'),
            'dry_run'          => false,
            'decision_via'     => $decision->via,
            'decision_ms'      => $decisionMs,
            'memory_saved'     => $memorySaved,
            'memory_ms'        => $memoryMs,
            'context_used'     => $contextUsed,
            'context_memories' => $contextMemories,
            'context_ms'       => $contextMs,
            // 'llm_ms'           => $llmMs,
            'total_ms'         => $totalMs,
            'http_status'      => 200,
            'idempotency_hit'  => false,
        ]);

        return $response;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Wraps engine in try/catch so a thrown exception never fails the request.
     */
    private function safeDecide(string $message, DecisionContext $context): DecisionResult
    {
        try {
            return $this->decisionEngine->decide($message, $context);
        } catch (Throwable $e) {
            // Return a safe default — never remember on engine error
            return new DecisionResult(
                remember:       false,
                type:           null,
                confidence:     0.0,
                matchedRules:   [],
                weights:        [],
                reason:         'Decision engine exception; defaulting to no-remember.',
                reasonCode:     'DECISION_ENGINE_ERROR',
                via:            'decision_error',
                ruleVersion:    (int) config('memory_rules.rule_version', 1),
                engineVersion:  (int) config('memory_rules.engine_version', 1),
                volatility:     'volatile',
            );
        }
    }


    /**
     * Build a 502 failure payload, cache it in idempotency store, and return it.
     * Memory state at time of failure is included so developers can see what succeeded.
     */
    // private function fail502(
    //     string  $requestId,
    //     DecisionResult $decision,
    //     bool    $memorySaved,
    //     ?string $memoryType,
    //     int     $decisionMs,
    //     int     $memoryMs,
    //     bool    $contextUsed,
    //     int     $contextMemories,
    //     int     $contextMs,
    //     int     $llmMs,
    //     int     $totalMs,
    //     int     $apiKeyId,
    //     ?string $idempotencyKey
    // ): array {
    //     Log::info('chat.latency', [
    //         'request_id'       => $requestId,
    //         'prompt_version'   => config('chat.prompt_version', 'v1'),
    //         'dry_run'          => false,
    //         'decision_via'     => $decision->via,
    //         'decision_ms'      => $decisionMs,
    //         'memory_saved'     => $memorySaved,
    //         'memory_ms'        => $memoryMs,
    //         'context_used'     => $contextUsed,
    //         'context_memories' => $contextMemories,
    //         'context_ms'       => $contextMs,
    //         'llm_ms'           => $llmMs,
    //         'total_ms'         => $totalMs,
    //         'http_status'      => 502,
    //         'idempotency_hit'  => false,
    //     ]);

    //     $payload = [
    //         'request_id'    => $requestId,
    //         'error'         => 'upstream_llm_unavailable',
    //         'message'       => 'The AI model is temporarily unavailable. Memory operations completed successfully.',
    //         'memory'        => [
    //             'saved'       => $memorySaved,
    //             'type'        => $memoryType,
    //             'reason'      => $decision->reason,
    //             'reason_code' => $decision->reasonCode,
    //             'via'         => $decision->via,
    //         ],
    //         '__http_status' => 502,
    //     ];

    //     if ($idempotencyKey !== null) {
    //         $this->idempotencyStore->put($apiKeyId, $idempotencyKey, $payload);
    //     }

    //     return $payload;
    // }


    /**
     * Parse memory types from formatted context lines.
     * Lines are formatted by MemoryContextAssemblyService as "[PREFERENCE] content..."
     */
    private function extractTypesFromContextLines(array $lines): array
    {
        $types = [];
        foreach ($lines as $line) {
            if (preg_match('/^\[([A-Z]+)\]/', (string) $line, $matches)) {
                $types[] = strtolower($matches[1]);
            }
        }
        return array_values(array_unique($types));
    }

    private function elapsedMs(int $startNs): int
    {
        return (int) round((hrtime(true) - $startNs) / 1_000_000);
    }
}