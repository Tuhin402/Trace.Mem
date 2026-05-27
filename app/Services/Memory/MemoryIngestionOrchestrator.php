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

    public function ingest(
        string $tenantId,
        string $userId,
        string $content,
        string $mode = 'ai_first'
    ): Collection {
        if ($mode === 'semantic_only') {
            return $this->memoryService->storeSemantic($tenantId, $userId, $content);
        }

        try {
            $items = $this->extractor->extract($content);

            if (empty($items)) {
                return $this->memoryService->storeSemantic($tenantId, $userId, $content);
            }

            return $this->memoryService->storeExtracted($tenantId, $userId, $items);
        } catch (Throwable $e) {
            return $this->memoryService->storeSemantic($tenantId, $userId, $content);
        }
    }
}