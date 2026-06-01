<?php

namespace App\Services\Memory;

use App\Models\Memory;

class MemoryContextAssemblyService
{
    public function __construct(
        private readonly MemoryService $memoryService,
        private readonly MemoryScoringService $scoring,
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
    ) {}

    public function assemble(
        string $tenantId,
        string $userId,
        string $query,
        int $tokenBudget = 600,
        int $candidateLimit = 50,
        bool $debug = false
    ): array {
        $query = $this->normalizeText($query);
        $querySegments = $this->semanticSegmenter->split($query);

        $candidates = $this->memoryService->candidates(
            $tenantId,
            $userId,
            $candidateLimit
        );

        $ranked = $candidates->map(function (Memory $memory) use ($query, $querySegments) {
            $analysis = $this->scoreCandidate($query, $querySegments, $memory);
            $promptLine = $this->formatForPrompt($memory);

            return [
                'memory' => $memory,
                'score' => $analysis['score'],
                'prompt_line' => $promptLine,
                'tokens' => $this->estimateTokens($promptLine),
                'breakdown' => $analysis['breakdown'],
            ];
        })
        ->filter(fn (array $row) => $row['score'] >= 0.25)
        ->sortByDesc('score')
        ->values();

        $selected = [];
        $usedTokens = 0;

        foreach ($ranked as $row) {
            $projectedTokens = $usedTokens + $row['tokens'];

            // Strict budget: if it does not fit, skip it.
            if ($projectedTokens > $tokenBudget) {
                continue;
            }

            $selected[] = [
                'memory_id' => $row['memory']->id,
                'type' => $row['memory']->type,
                'content' => $row['memory']->content,
                'score' => round($row['score'], 4),
                'prompt_line' => $row['prompt_line'],
            ];

            $usedTokens = $projectedTokens;
        }

        $contextLines = array_map(
            fn (array $item) => $item['prompt_line'],
            $selected
        );

        $response = [
            'query' => $query,
            'token_budget' => $tokenBudget,
            'token_used' => $usedTokens,
            'total_candidates' => $candidates->count(),
            'selected_count' => count($selected),
            'context' => $contextLines,
            'context_text' => implode("\n", $contextLines),
        ];

        if ($debug) {
            $response['debug'] = [
                'query_segments' => $querySegments,
                'candidate_limit' => $candidateLimit,
                'selection_threshold' => 0.25,
                'ranked_candidates' => $ranked->take(20)->map(function (array $row) {
                    return [
                        'memory_id' => $row['memory']->id,
                        'type' => $row['memory']->type,
                        'content' => $row['memory']->content,
                        'score' => round($row['score'], 4),
                        'tokens' => $row['tokens'],
                        'prompt_line' => $row['prompt_line'],
                        'breakdown' => $row['breakdown'],
                    ];
                })->values()->all(),
                'selected_items' => $selected,
            ];
        }

        return $response;
    }

    private function scoreCandidate(string $query, array $querySegments, Memory $memory): array
    {
        $baseScore = $this->scoring->recallScore($memory);

        $memoryText = $this->normalizeText(
            (string) ($memory->normalized_content ?: $memory->content)
        );

        $queryTokens = $this->tokenize($query);
        $memoryTokens = $this->tokenize($memoryText);

        $tokenOverlap = count(array_intersect($queryTokens, $memoryTokens));
        $tokenOverlapBonus = min(0.8, $tokenOverlap * 0.15);

        $segmentBonus = 0.0;
        $matchedSegments = [];

        foreach ($querySegments as $segment) {
            $segmentType = (string) ($segment['type'] ?? '');
            $segmentText = $this->normalizeText((string) ($segment['content'] ?? ''));

            if ($segmentText === '') {
                continue;
            }

            if ($segmentType === (string) $memory->type) {
                $segmentTokens = $this->tokenize($segmentText);
                $segmentOverlap = count(array_intersect($segmentTokens, $memoryTokens));

                if ($segmentOverlap > 0) {
                    $segmentBonus += min(0.25, 0.08 + ($segmentOverlap * 0.04));
                    $matchedSegments[] = $segmentText;
                }

                if ($this->hasPhraseOverlap($segmentText, $memoryText)) {
                    $segmentBonus += 0.12;
                    $matchedSegments[] = $segmentText;
                }
            }
        }

        $phraseBonus = 0.0;
        if ($memoryText !== '' && $this->hasPhraseOverlap($query, $memoryText)) {
            $phraseBonus = 0.35;
        }

        $recencyBonus = $this->recencyBoost($memory);
        $importanceBonus = $this->importanceBoost($memory);

        $meta = is_array($memory->metadata) ? $memory->metadata : [];
        $conflictScore = (float) ($meta['conflict_score'] ?? 0);
        $conflictPenalty = 0.0;

        if ($conflictScore > 0) {
            $conflictPenalty = min(0.4, $conflictScore * 0.1);
        }

        $confidencePenalty = ((float) $memory->confidence < 0.4) ? 0.1 : 0.0;

        // ── Metadata-aware adjustments ───────────────────────────
        $meta = is_array($memory->metadata) ? $memory->metadata : [];
        $metadataPenalty = 0.0;

        // Code snippets without explicit_remember are deprioritized
        if (($meta['source_kind'] ?? null) === 'code_snippet'
            && ! ($meta['explicit_remember'] ?? false)) {
            $metadataPenalty += 0.10;
        }

        // Third-party statements deprioritized unless query mentions the entity
        if (($meta['subject'] ?? 'self') === 'other') {
            $queryOverlapsMemory = count(array_intersect($queryTokens, $memoryTokens)) >= 2;
            if (! $queryOverlapsMemory) {
                $metadataPenalty += 0.08;
            }
        }

        // Transient memories deprioritized in context assembly
        if ($meta['transient'] ?? false) {
            $metadataPenalty += 0.06;
        }

        $final = $baseScore
            + $tokenOverlapBonus
            + $segmentBonus
            + $phraseBonus
            + $recencyBonus
            + $importanceBonus
            - $conflictPenalty
            - $confidencePenalty
            - $metadataPenalty;

        return [
            'score' => max(0.0, $final),
            'breakdown' => [
                'base_score'          => round($baseScore, 4),
                'token_overlap_bonus' => round($tokenOverlapBonus, 4),
                'segment_bonus'       => round($segmentBonus, 4),
                'matched_segments'    => array_values(array_unique($matchedSegments)),
                'phrase_bonus'        => round($phraseBonus, 4),
                'recency_bonus'       => round($recencyBonus, 4),
                'importance_bonus'    => round($importanceBonus, 4),
                'conflict_penalty'    => round($conflictPenalty, 4),
                'confidence_penalty'  => round($confidencePenalty, 4),
                'metadata_penalty'    => round($metadataPenalty, 4),
                'final_score'         => round(max(0.0, $final), 4),
            ],
        ];
    }

    private function recencyBoost(Memory $memory): float
    {
        $anchor = $memory->last_accessed_at ?: $memory->updated_at ?: $memory->created_at;

        if (! $anchor) {
            return 0.0;
        }

        $days = now()->diffInDays($anchor);

        return match (true) {
            $days <= 1 => 0.20,
            $days <= 7 => 0.12,
            $days <= 30 => 0.06,
            default => 0.0,
        };
    }

    private function importanceBoost(Memory $memory): float
    {
        $importance = (float) $memory->importance;
        $confidence = (float) $memory->confidence;

        return min(0.35, ($importance * 0.15) + ($confidence * 0.10));
    }

    private function hasPhraseOverlap(string $query, string $memoryText): bool
    {
        $query = $this->normalizeText($query);
        $memoryText = $this->normalizeText($memoryText);

        if ($query === '' || $memoryText === '') {
            return false;
        }

        return str_contains($query, $memoryText) || str_contains($memoryText, $query);
    }

    private function formatForPrompt(Memory $memory): string
    {
        return sprintf(
            '[%s] %s',
            strtoupper((string) $memory->type),
            trim((string) $memory->content)
        );
    }

    private function estimateTokens(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 1;
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $wordCount = count(array_filter($words, fn ($w) => $w !== ''));
        $charCount = mb_strlen($text, 'UTF-8');
        $punctuationCount = preg_match_all('/[,:;.!?\-(){}\[\]"]/u', $text) ?: 0;

        // Slightly conservative heuristic for safer budgeting.
        $estimate = ($charCount / 3.8) + ($wordCount * 0.45) + ($punctuationCount * 0.15);

        return max(1, (int) ceil($estimate));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return $text ?? '';
    }

    private function tokenize(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $parts = preg_split('/\s+/u', $this->normalizeText($text)) ?: [];

        return array_values(array_filter($parts, fn ($v) => $v !== ''));
    }
}