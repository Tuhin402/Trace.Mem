<?php

namespace App\Services\Memory;

use App\Services\MemoryExtractionService;
use Illuminate\Support\Collection;
use Throwable;

class MemoryIngestionOrchestrator
{
    public function __construct(
        private readonly MemoryService $memoryService,
        private readonly MemoryExtractionService $extractor,
    ) {}

    /**
     * Ingest a message into memory storage.
     *
     * @param  string $tenantId
     * @param  string $userId
     * @param  string $content       Raw user message
     * @param  string $mode          'ai_first' | 'semantic_only'
     * @param  array  $decisionMeta  Optional metadata from MemoryDecisionEngine.
     *                               When provided, fields are merged into every
     *                               stored memory's metadata for full traceability.
     *                               Keys: engine_version, rule_version, matched_rule,
     *                               matched_rules, reason_code, volatility, confidence, via.
     *                               Existing callers omit this param — fully backward-compatible.
     */
    public function ingest(
        string $tenantId,
        string $userId,
        string $content,
        string $mode         = 'ai_first',
        array  $decisionMeta = []
    ): Collection {
        if ($mode === 'semantic_only') {
            return $this->memoryService->storeSemantic($tenantId, $userId, $content, $decisionMeta);
        }

        try {
            $items = $this->extractor->extract($content);

            if (empty($items)) {
                return $this->memoryService->storeSemantic($tenantId, $userId, $content, $decisionMeta);
            }

            return $this->memoryService->storeExtracted($tenantId, $userId, $items, $decisionMeta);
        } catch (Throwable $e) {
            return $this->memoryService->storeSemantic($tenantId, $userId, $content, $decisionMeta);
        }
    }
}