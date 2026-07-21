<?php

namespace App\Services\Memory;

use App\Jobs\ReinforceMemoriesJob;
use App\Models\Memory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemoryService
{
    private const ALLOWED_TYPES = ['preference', 'fact', 'rule', 'skill'];

    public function __construct(
        private readonly MemoryNormalizationService $normalizer,
        private readonly MemoryScoringService $scoring,
        private readonly MemoryDeduplicationService $deduper,
        private readonly MemoryConflictService $conflicts,
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
        private readonly MemoryLifecycleService $lifecycle,
    ) {}

    public function store(
        string $tenantId,
        string $userId,
        string $type,
        string $content,
        float $confidence = 0.5,
        array $metadata = []
    ): Memory {
        $type = $this->sanitizeType($type);

        return DB::transaction(function () use ($tenantId, $userId, $type, $content, $confidence, $metadata) {
            // ── Transient confidence cap ─────────────────────────────
            if ($metadata['transient'] ?? false) {
                $confidence = min($confidence, 0.35);
            }

            // ── code-safe normalization branch ──────────────────────
            $isCodeSnippet = (($metadata['source_kind'] ?? 'plain') === 'code_snippet');

            $normalizedContent = $isCodeSnippet
                ? $this->normalizer->normalizeCode($content)
                : $this->normalizer->normalize($content);

            $contentHash = $this->normalizer->hash($tenantId, $userId, $type, $normalizedContent);

            // ── merge pipeline metadata (preserve existing source tag) ─
            $mergedMetadata = array_merge(
                ['source' => 'semantic_segmenter'],
                is_array($metadata) ? $metadata : []
            );

            $existing = $this->deduper->findDuplicate($tenantId, $userId, $type, $contentHash);

            if ($existing) {
                $existing->content = $content;
                $existing->normalized_content = $normalizedContent;
                $existing->content_hash = $contentHash;
                $existing->metadata = $mergedMetadata;
                $existing->importance = number_format(max(
                        (float) $existing->importance,
                        $this->scoring->baseImportance($type, $content)
                    ), 4, '.', ''
                );
                $existing->confidence = number_format(min(((float) $existing->confidence) + 0.1, 1.0), 4, '.', '');
                $existing->decay_score = number_format(1.0, 4, '.', '');
                $existing->save();

                $this->conflicts->resolve($existing);

                return $existing->fresh();
            }

            $memory = Memory::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $type,
                'content' => $content,
                'normalized_content' => $normalizedContent,
                'content_hash' => $contentHash,
                'importance' => $this->scoring->baseImportance($type, $content),
                'confidence' => $confidence,
                'decay_score' => 1.0,
                'last_accessed_at' => null,
                'access_count' => 0,
                'metadata' => $mergedMetadata,
            ]);

            $this->conflicts->resolve($memory);

            return $memory->fresh();
        });
    }

    public function storeSemantic(
        string $tenantId,
        string $userId,
        string $content,
        array  $decisionMeta = []
    ): Collection {
        $items = $this->semanticSegmenter->split($content);
        return $this->storeExtracted(
            $tenantId,
            $userId,
            $items,
            $decisionMeta
        );
    }

    public function storeExtracted(
        string $tenantId,
        string $userId,
        array  $items,
        array  $decisionMeta = []
    ): Collection {
        return DB::transaction(function () use ($tenantId, $userId, $items, $decisionMeta) {
            $saved = [];

            foreach ($items as $item) {
                $type = $this->sanitizeType($item['type'] ?? 'fact');
                $content = trim($item['content'] ?? '');

                if ($content === '') {
                    continue;
                }

                // Forward segment metadata (if present) to store()
                $segmentMetadata = is_array($item['metadata'] ?? null)
                    ? $item['metadata']
                    : [];

                // Merge decision engine traceability metadata (engine_version, rule_version, etc.)
                if (! empty($decisionMeta)) {
                    $segmentMetadata = array_merge($decisionMeta, $segmentMetadata);
                }

                // Skip storage for gated items (e.g., raw code without explicit remember)
                if ($segmentMetadata['skip_storage'] ?? false) {
                    continue;
                }

                $saved[] = $this->store(
                    $tenantId,
                    $userId,
                    $type,
                    $content,
                    (float) ($item['confidence'] ?? 0.5),
                    $segmentMetadata
                );
            }

            return collect($saved);
        });
    }

    public function recall(
        string $tenantId,
        string $userId,
        int $limit = 5,
        int $page = 1,
        string $sortBy = 'recall_score',
        string $sortOrder = 'desc'
    ): Collection {
        $query = Memory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereIn('status', ['active', 'stale']);
            });

        if ($sortBy !== 'recall_score') {
            $query->orderBy($sortBy, $sortOrder);
            
            $offset = ($page - 1) * $limit;
            $query->offset($offset)->limit($limit);
            
            $memories = $query->get();
        } else {
            $allMemories = $query->get();
            
            if ($sortOrder === 'desc') {
                $sorted = $allMemories->sortByDesc(fn (Memory $memory) => $this->scoring->recallScore($memory));
            } else {
                $sorted = $allMemories->sortBy(fn (Memory $memory) => $this->scoring->recallScore($memory));
            }
            
            $offset = ($page - 1) * $limit;
            $memories = $sorted->slice($offset, $limit)->values();
        }

        // ── Batched async reinforcement ────────────────────────────────────
        // One job per recall request — not one per memory. This eliminates
        // N synchronous DB writes from the hot path. The caller receives the
        // pre-reinforcement state of each memory (response shape unchanged).
        $ids = $memories->pluck('id')->all();
        if (! empty($ids)) {
            ReinforceMemoriesJob::dispatch($ids)->onQueue('low');
        }

        return $memories->values();
    }

    public function candidates(string $tenantId, string $userId, int $limit = 50): Collection
    {
        $limit = max(1, min($limit, 100));

        return Memory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereIn('status', ['active', 'stale']);
            })
            ->orderByDesc('importance')
            ->orderByDesc('confidence')
            ->orderByDesc('decay_score')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    private function sanitizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, self::ALLOWED_TYPES, true) ? $type : 'fact';
    }

}