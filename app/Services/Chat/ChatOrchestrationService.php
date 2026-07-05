<?php

namespace App\Services\Chat;

use App\Services\Memory\MemoryContextAssemblyService;
use App\Services\Memory\MemoryIngestionOrchestrator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates the full POST /api/v1/chat pipeline.
 *
 * Execution order (non-dry-run):
 *   1. Idempotency check  → return cached response immediately if hit
 *   2. Classify message   → HybridClassifierService (heuristic or NIM)
 *   3. Memory ingestion   → MemoryIngestionOrchestrator (existing, unchanged)
 *   4. Context assembly   → MemoryContextAssemblyService (existing, unchanged)
 *   5. Prompt build       → PromptAssemblyService
 *   6. NIM chat call      → one LLM call, 30 s timeout, no retry
 *   7. Build response
 *   8. Cache in idempotency store
 *   9. Log latency metrics (no user content)
 *
 * Failure isolation:
 *   Steps 2, 3, 4 are each wrapped independently. A failure in any one
 *   continues the pipeline (with degraded output) rather than aborting.
 *   Only step 6 (LLM) can produce a non-200 response (502).
 *
 * One LLM call guaranteed:
 *   The hybrid classifier adds zero NIM calls for heuristic-matched messages.
 *   Even for NIM-classified messages, the classifier call is separate and cheap
 *   (max_tokens=100, temperature=0). The main chat NIM call is always exactly one.
 */
class ChatOrchestrationService
{
    public function __construct(
        private readonly HybridClassifierService      $classifier,
        private readonly IdempotencyStore             $idempotencyStore,
        private readonly MemoryIngestionOrchestrator  $orchestrator,
        private readonly MemoryContextAssemblyService $contextAssembler,
        private readonly PromptAssemblyService        $promptAssembler,
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

        // ── 2. Dry-run: classifier only, zero side effects ───────────────────
        if ($dryRun) {
            $classifierStart = hrtime(true);
            $decision        = $this->safeClassify($message, $memoryMode);
            $classifierMs    = $this->elapsedMs($classifierStart);

            Log::info('chat.latency', [
                'request_id'     => $requestId,
                'prompt_version' => config('chat.prompt_version', 'v1'),
                'dry_run'        => true,
                'classifier_via' => $decision['via'],
                'classifier_ms'  => $classifierMs,
                'total_ms'       => $classifierMs,
                'http_status'    => 200,
            ]);

            return [
                'request_id'     => $requestId,
                'dry_run'        => true,
                'would_remember' => $decision['remember'],
                'type'           => $decision['type'],
                'reason'         => $decision['reason'],
                'via'            => $decision['via'],
            ];
        }

        // ── 3. Classifier ─────────────────────────────────────────────────────
        $classifierStart   = hrtime(true);
        $decision          = $this->safeClassify($message, $memoryMode);
        $classifierMs      = $this->elapsedMs($classifierStart);
        $classifierWarning = $decision['_warning'] ?? null;

        // ── 4. Memory ingestion ───────────────────────────────────────────────
        $memorySaved   = false;
        $memoryType    = null;
        $memoryWarning = null;
        $memoryMs      = 0;

        if ($decision['remember'] && $memoryMode !== 'off') {
            $memoryStart = hrtime(true);
            try {
                $this->orchestrator->ingest($tenantId, $userId, $message, 'ai_first');
                $memorySaved = true;
                $memoryType  = $decision['type'];
            } catch (Throwable $e) {
                $memoryWarning = 'Memory storage failed; chat continues.';
            }
            $memoryMs = $this->elapsedMs($memoryStart);
        }

        // ── 5. Context assembly ───────────────────────────────────────────────
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

        // ── 6. Prompt assembly ────────────────────────────────────────────────
        $systemPrompt = $this->promptAssembler->buildSystemPrompt($contextText, $contextUsed);
        $messages     = $this->promptAssembler->buildMessages($systemPrompt, $message);

        // ── 7. NIM chat call (ONE call, 30 s timeout, no retry) ──────────────
        $llmStart = hrtime(true);

        try {
            $nimResponse = Http::withToken(config('services.nvidia_nim_openai.api_key'))
                ->acceptJson()
                ->timeout(30)
                ->post(config('services.nvidia_nim_openai.base_url') . '/chat/completions', [
                    'model'       => config('services.nvidia_nim_openai.model', 'openai/gpt-oss-20b'),
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'top_p'       => 1,
                    'max_tokens'  => 1024,
                    'stream'      => false,
                ]);

            $llmMs   = $this->elapsedMs($llmStart);
            $totalMs = $this->elapsedMs($totalStart);

            if (! $nimResponse->successful()) {
                return $this->fail502(
                    $requestId, $decision, $memorySaved, $memoryType,
                    $classifierMs, $decision['via'], $memoryMs,
                    $contextUsed, $contextMemories, $contextMs,
                    $llmMs, $totalMs,
                    $apiKeyId, $idempotencyKey
                );
            }

            $reply = (string) $nimResponse->json('choices.0.message.content', '');

        } catch (Throwable $e) {
            $llmMs   = $this->elapsedMs($llmStart);
            $totalMs = $this->elapsedMs($totalStart);

            return $this->fail502(
                $requestId, $decision, $memorySaved, $memoryType,
                $classifierMs, $decision['via'], $memoryMs,
                $contextUsed, $contextMemories, $contextMs,
                $llmMs, $totalMs,
                $apiKeyId, $idempotencyKey
            );
        }

        $totalMs = $this->elapsedMs($totalStart);

        // ── 8. Build success response ─────────────────────────────────────────
        $response = [
            'request_id' => $requestId,
            'reply'      => $reply,
            'memory'     => [
                'saved'  => $memorySaved,
                'type'   => $memoryType,
                'reason' => $decision['reason'],
                'via'    => $decision['via'],
            ],
            'context'    => [
                'used'            => $contextUsed,
                'memories'        => $contextMemories,
                'tokens'          => $contextTokens,
                'candidate_count' => $candidateCount,
                'returned_count'  => $contextMemories,
                'assembled_from'  => $assembledFrom,
            ],
            'provider'   => 'nvidia',
            'model'      => config('services.nvidia_nim_openai.model', 'openai/gpt-oss-20b'),
            'latency_ms' => [
                'classifier' => $classifierMs,
                'memory'     => $memoryMs,
                'context'    => $contextMs,
                'llm'        => $llmMs,
                'total'      => $totalMs,
            ],
        ];

        // debug block — only when explicitly requested
        if ($debug) {
            $warnings = array_values(array_filter([
                $classifierWarning,
                $memoryWarning,
                $contextWarning,
            ]));

            $response['debug'] = [
                'prompt_version'        => config('chat.prompt_version', 'v1'),
                'classifier_confidence' => $decision['confidence'],
                'classifier_via'        => $decision['via'],
                'context_segments'      => $contextText !== '' ? array_filter(explode("\n", trim($contextText))) : [],
                'circuit_breaker'       => $this->classifier->getCircuitState(),
                'warnings'              => $warnings,
            ];
        }

        // ── 9. Idempotency + latency log ──────────────────────────────────────
        if ($idempotencyKey !== null) {
            $this->idempotencyStore->put($apiKeyId, $idempotencyKey, $response);
        }

        Log::info('chat.latency', [
            'request_id'        => $requestId,
            'prompt_version'    => config('chat.prompt_version', 'v1'),
            'dry_run'           => false,
            'classifier_via'    => $decision['via'],
            'classifier_ms'     => $classifierMs,
            'memory_saved'      => $memorySaved,
            'memory_ms'         => $memoryMs,
            'context_used'      => $contextUsed,
            'context_memories'  => $contextMemories,
            'context_ms'        => $contextMs,
            'circuit_state'     => $this->classifier->getCircuitState(),
            'llm_ms'            => $llmMs,
            'total_ms'          => $totalMs,
            'http_status'       => 200,
            'idempotency_hit'   => false,
        ]);

        return $response;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Wraps classifier in a try/catch so a thrown exception never fails the request.
     */
    private function safeClassify(string $message, string $mode): array
    {
        try {
            return $this->classifier->classify($message, $mode);
        } catch (Throwable $e) {
            return [
                'remember'   => false,
                'type'       => null,
                'reason'     => 'Classifier exception; defaulting to no-remember.',
                'confidence' => 0.0,
                'via'        => 'classifier_error',
                '_warning'   => 'Classifier threw: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build a 502 failure payload, cache it in idempotency store, and return it.
     * Includes memory state at time of failure so developers can see what did succeed.
     */
    private function fail502(
        string  $requestId,
        array   $decision,
        bool    $memorySaved,
        ?string $memoryType,
        int     $classifierMs,
        string  $classifierVia,
        int     $memoryMs,
        bool    $contextUsed,
        int     $contextMemories,
        int     $contextMs,
        int     $llmMs,
        int     $totalMs,
        int     $apiKeyId,
        ?string $idempotencyKey
    ): array {
        Log::info('chat.latency', [
            'request_id'       => $requestId,
            'prompt_version'   => config('chat.prompt_version', 'v1'),
            'dry_run'          => false,
            'classifier_via'   => $classifierVia,
            'classifier_ms'    => $classifierMs,
            'memory_saved'     => $memorySaved,
            'memory_ms'        => $memoryMs,
            'context_used'     => $contextUsed,
            'context_memories' => $contextMemories,
            'context_ms'       => $contextMs,
            'circuit_state'    => $this->classifier->getCircuitState(),
            'llm_ms'           => $llmMs,
            'total_ms'         => $totalMs,
            'http_status'      => 502,
            'idempotency_hit'  => false,
        ]);

        $payload = [
            'request_id'    => $requestId,
            'error'         => 'upstream_llm_unavailable',
            'message'       => 'The AI model is temporarily unavailable. Memory operations completed successfully.',
            'memory'        => [
                'saved'  => $memorySaved,
                'type'   => $memoryType,
                'reason' => $decision['reason'],
                'via'    => $decision['via'],
            ],
            '__http_status' => 502,
        ];

        if ($idempotencyKey !== null) {
            $this->idempotencyStore->put($apiKeyId, $idempotencyKey, $payload);
        }

        return $payload;
    }

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
