<?php

namespace App\Services\Memory;

use App\Models\Memory;
use Carbon\CarbonImmutable;

class MemoryContextAssemblyService
{
    public function __construct(
        private readonly MemoryService $memoryService,
        private readonly MemoryScoringService $scoring,
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
        private readonly MemoryTemporalService $temporalService,
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
        $queryTemporal = $this->temporalService->extract($query);

        // ── NEW: Classify the query for temporal-aware scoring ────
        $queryClassification = $this->temporalService->classifyQuery($query);
        $weights = $this->resolveWeights($queryClassification);

        $candidates = $this->memoryService->candidates(
            $tenantId,
            $userId,
            $candidateLimit
        );

        $ranked = $candidates->map(function (Memory $memory) use ($query, $querySegments, $queryTemporal, $queryClassification, $weights) {
            $analysis = $this->scoreCandidate($query, $querySegments, $queryTemporal, $queryClassification, $weights, $memory);
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
                'query_classification' => $queryClassification,
                'scoring_weights' => $weights,
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

    // ═════════════════════════════════════════════════════════════
    //  Dynamic weight system — adapts scoring to query type
    // ═════════════════════════════════════════════════════════════

    /**
     * Resolve scoring weights based on query classification.
     *
     * For non-schedule queries, all multipliers are at their default
     * (1.0) and all additive schedule boosts are 0, preserving the
     * original scoring behaviour exactly.
     *
     * Weight values are loaded from config/memory_scoring.php so they
     * can be tuned per-environment without code changes.
     */
    private function resolveWeights(array $queryClassification): array
    {
        $isScheduleQuery = $queryClassification['is_schedule_like']
            || $queryClassification['is_future_oriented']
            || ($queryClassification['temporal_extract']['has_temporal'] ?? false);

        if (! $isScheduleQuery) {
            // ── Default profile: identical to pre-change behaviour ──
            return config('memory_scoring.default', [
                'temporal_boost_cap'         => 0.35,
                'temporal_penalty_base'      => 0.05,
                'importance_weight'          => 1.0,
                'recency_weight'             => 1.0,
                'schedule_intent_boost'      => 0.0,
                'recurring_boost'            => 0.0,
                'exact_date_match_boost'     => 0.0,
                'non_temporal_penalty'       => 0.0,
                'future_relevance_boost'     => 0.0,
            ]);
        }

        $strength = $queryClassification['intent_strength'] ?? 0.0;
        $cfg = config('memory_scoring.schedule', []);
        $defaults = config('memory_scoring.default', []);

        // ── Schedule-aware profile: strength scales the shift ────
        $baseTemporalCap    = (float) ($defaults['temporal_boost_cap'] ?? 0.35);
        $basePenalty        = (float) ($defaults['temporal_penalty_base'] ?? 0.05);

        $capAdd             = (float) ($cfg['temporal_boost_cap_add'] ?? 0.25);
        $penaltyAdd         = (float) ($cfg['temporal_penalty_base_add'] ?? 0.15);
        $importReduction    = (float) ($cfg['importance_weight_reduction'] ?? 0.50);
        $importFloor        = (float) ($cfg['importance_weight_floor'] ?? 0.35);
        $recencyReduction   = (float) ($cfg['recency_weight_reduction'] ?? 0.30);
        $recencyFloor       = (float) ($cfg['recency_weight_floor'] ?? 0.50);

        return [
            'temporal_boost_cap'         => round($baseTemporalCap + ($capAdd * $strength), 4),
            'temporal_penalty_base'      => round($basePenalty + ($penaltyAdd * $strength), 4),
            'importance_weight'          => round(max($importFloor, 1.0 - ($importReduction * $strength)), 4),
            'recency_weight'             => round(max($recencyFloor, 1.0 - ($recencyReduction * $strength)), 4),
            'schedule_intent_boost'      => round(((float) ($cfg['schedule_intent_boost'] ?? 0.20)) * $strength, 4),
            'recurring_boost'            => $queryClassification['is_recurring']
                ? round(((float) ($cfg['recurring_boost'] ?? 0.15)) * $strength, 4)
                : 0.0,
            'exact_date_match_boost'     => $queryClassification['is_exact_date']
                ? round(((float) ($cfg['exact_date_match_boost'] ?? 0.25)) * $strength, 4)
                : 0.0,
            'non_temporal_penalty'       => round(((float) ($cfg['non_temporal_penalty'] ?? 0.15)) * $strength, 4),
            'future_relevance_boost'     => $queryClassification['is_future_oriented']
                ? round(((float) ($cfg['future_relevance_boost'] ?? 0.10)) * $strength, 4)
                : 0.0,
        ];
    }

    // ═════════════════════════════════════════════════════════════
    //  Candidate scoring
    // ═════════════════════════════════════════════════════════════

    private function scoreCandidate(
        string $query,
        array $querySegments,
        array $queryTemporal,
        array $queryClassification,
        array $weights,
        Memory $memory
    ): array {
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

        // ── Apply weighted recency and importance ────────────────
        $recencyBonus = $this->recencyBoost($memory) * $weights['recency_weight'];
        $importanceBonus = $this->importanceBoost($memory) * $weights['importance_weight'];

        $meta = is_array($memory->metadata) ? $memory->metadata : [];
        $conflictScore = (float) ($meta['conflict_score'] ?? 0);
        $conflictPenalty = 0.0;

        if ($conflictScore > 0) {
            $conflictPenalty = min(0.4, $conflictScore * 0.1);
        }

        $confidencePenalty = ((float) $memory->confidence < 0.4) ? 0.1 : 0.0;

        // ── Metadata-aware adjustments ───────────────────────────
        $memoryTemporal = is_array($meta['temporal'] ?? null) ? $meta['temporal'] : [];
        $metadataPenalty = 0.0;

        $temporalBoost = $this->temporalMatchScore($queryTemporal, $memoryTemporal, $queryClassification);
        $temporalBoost = min($temporalBoost, $weights['temporal_boost_cap']);

        // Temporal penalty: query has temporal but memory doesn't
        $temporalPenalty = 0.0;
        if (($queryTemporal['has_temporal'] ?? false) && ! ($memoryTemporal['has_temporal'] ?? false)) {
            $temporalPenalty = $weights['temporal_penalty_base'];
        }

        // ── NEW: Schedule-aware scoring factors ─────────────────
        $scheduleIntentBoost = 0.0;
        $recurringBoost = 0.0;
        $exactDateMatchBoost = 0.0;
        $futureRelevanceBoost = 0.0;
        $nonTemporalPenalty = 0.0;

        $memoryHasTemporal = ($memoryTemporal['has_temporal'] ?? false);
        $memoryIsSchedule = ($memoryTemporal['schedule_like'] ?? false) || ($meta['schedule_event'] ?? false);
        $memoryIsRecurring = ($memoryTemporal['kind'] ?? null) === 'recurring';
        $isScheduleQuery = $queryClassification['is_schedule_like']
            || $queryClassification['is_future_oriented']
            || ($queryClassification['temporal_extract']['has_temporal'] ?? false);

        if ($isScheduleQuery) {
            // ── Schedule intent boost: reward memories flagged as schedule/temporal ──
            if ($memoryHasTemporal || $memoryIsSchedule) {
                $scheduleIntentBoost = $weights['schedule_intent_boost'];
            }

            // ── Recurring boost: reward recurring memories for recurring queries ──
            if ($queryClassification['is_recurring'] && $memoryIsRecurring) {
                $recurringBoost = $weights['recurring_boost'];
            }

            // ── Exact date match boost: reward same-day memories ──
            if ($queryClassification['is_exact_date'] && $memoryHasTemporal) {
                $qStart = $this->parseTemporalDate($queryTemporal['start_at'] ?? null);
                $mStart = $this->parseTemporalDate($memoryTemporal['start_at'] ?? null);

                if ($qStart && $mStart && $qStart->isSameDay($mStart)) {
                    $exactDateMatchBoost = $weights['exact_date_match_boost'];
                }
            }

            // ── Future relevance boost: reward future-dated memories ──
            if ($queryClassification['is_future_oriented'] && $memoryHasTemporal) {
                $mStart = $this->parseTemporalDate($memoryTemporal['start_at'] ?? null);

                if ($mStart && $mStart->isFuture()) {
                    $futureRelevanceBoost = $weights['future_relevance_boost'];
                }
            }

            // ── Non-temporal penalty: penalize non-temporal memories in schedule queries ──
            if (! $memoryHasTemporal && ! $memoryIsSchedule) {
                $nonTemporalPenalty = $weights['non_temporal_penalty'];
            }
        }

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
            + $temporalBoost
            + $scheduleIntentBoost
            + $recurringBoost
            + $exactDateMatchBoost
            + $futureRelevanceBoost
            - $conflictPenalty
            - $confidencePenalty
            - $metadataPenalty
            - $temporalPenalty
            - $nonTemporalPenalty;

        // ── Build explainability tag for debug ───────────────────
        $whyRanked = $this->buildWhyRanked(
            $temporalBoost, $scheduleIntentBoost, $recurringBoost,
            $exactDateMatchBoost, $futureRelevanceBoost, $nonTemporalPenalty,
            $tokenOverlapBonus, $phraseBonus
        );

        return [
            'score' => max(0.0, $final),
            'breakdown' => [
                'base_score'              => round($baseScore, 4),
                'token_overlap_bonus'     => round($tokenOverlapBonus, 4),
                'segment_bonus'           => round($segmentBonus, 4),
                'matched_segments'        => array_values(array_unique($matchedSegments)),
                'phrase_bonus'            => round($phraseBonus, 4),
                'recency_bonus'           => round($recencyBonus, 4),
                'importance_bonus'        => round($importanceBonus, 4),
                'temporal_boost'          => round($temporalBoost, 4),
                'schedule_intent_boost'   => round($scheduleIntentBoost, 4),
                'recurring_boost'         => round($recurringBoost, 4),
                'exact_date_match_boost'  => round($exactDateMatchBoost, 4),
                'future_relevance_boost'  => round($futureRelevanceBoost, 4),
                'conflict_penalty'        => round($conflictPenalty, 4),
                'confidence_penalty'      => round($confidencePenalty, 4),
                'metadata_penalty'        => round($metadataPenalty, 4),
                'temporal_penalty'        => round($temporalPenalty, 4),
                'non_temporal_penalty'    => round($nonTemporalPenalty, 4),
                'final_score'             => round(max(0.0, $final), 4),
                'why_ranked'              => $whyRanked,
            ],
        ];
    }

    /**
     * Build a human-readable explanation of why a memory ranked where it did.
     */
    private function buildWhyRanked(
        float $temporalBoost,
        float $scheduleIntentBoost,
        float $recurringBoost,
        float $exactDateMatchBoost,
        float $futureRelevanceBoost,
        float $nonTemporalPenalty,
        float $tokenOverlapBonus,
        float $phraseBonus
    ): string {
        $reasons = [];

        if ($exactDateMatchBoost > 0)   { $reasons[] = 'exact_date_match'; }
        if ($temporalBoost > 0)         { $reasons[] = 'temporal_match'; }
        if ($scheduleIntentBoost > 0)   { $reasons[] = 'schedule_intent'; }
        if ($recurringBoost > 0)        { $reasons[] = 'recurring_match'; }
        if ($futureRelevanceBoost > 0)  { $reasons[] = 'future_relevant'; }
        if ($phraseBonus > 0)           { $reasons[] = 'phrase_match'; }
        if ($tokenOverlapBonus > 0)     { $reasons[] = 'token_overlap'; }
        if ($nonTemporalPenalty > 0)    { $reasons[] = 'non_temporal_penalized'; }

        return $reasons ? implode(' + ', $reasons) : 'base_score_only';
    }

    // ═════════════════════════════════════════════════════════════
    //  Temporal match scoring — classification-aware
    // ═════════════════════════════════════════════════════════════

    /**
     * Score how well a memory's temporal metadata matches the query's
     * temporal metadata, with classification-aware boosting.
     */
    private function temporalMatchScore(array $queryTemporal, array $memoryTemporal, array $queryClassification = []): float
    {
        if (! ($queryTemporal['has_temporal'] ?? false) && ! ($memoryTemporal['has_temporal'] ?? false)) {
            // Neither has temporal — no boost, no penalty
            return 0.0;
        }

        // ── Vague schedule query with no resolved dates ──────────
        // If the query is schedule-like but extract() found no concrete
        // dates, we still want to reward temporal memories.
        if (! ($queryTemporal['has_temporal'] ?? false) && ($memoryTemporal['has_temporal'] ?? false)) {
            $isScheduleQuery = $queryClassification['is_schedule_like'] ?? false;
            if ($isScheduleQuery) {
                // Mild boost: memory is temporal, query has schedule intent
                $boost = 0.10;
                if ($memoryTemporal['schedule_like'] ?? false) {
                    $boost = 0.15;
                }
                if (($memoryTemporal['kind'] ?? null) === 'recurring' && ($queryClassification['is_recurring'] ?? false)) {
                    $boost = 0.20;
                }
                return $boost;
            }
            return 0.0;
        }

        // ── Query has temporal but memory doesn't ────────────────
        if (($queryTemporal['has_temporal'] ?? false) && ! ($memoryTemporal['has_temporal'] ?? false)) {
            return 0.0;
        }

        // ── Both have temporal: detailed matching ────────────────
        $qStart = $this->parseTemporalDate($queryTemporal['start_at'] ?? null);
        $qEnd   = $this->parseTemporalDate($queryTemporal['end_at'] ?? null);
        $mStart = $this->parseTemporalDate($memoryTemporal['start_at'] ?? null);
        $mEnd   = $this->parseTemporalDate($memoryTemporal['end_at'] ?? null);

        // Exact same-day match: strongest
        if ($qStart && $mStart && $qStart->isSameDay($mStart)) {
            return 0.50;
        }

        // Range overlap: query range contains memory or vice versa
        if ($qStart && $mStart && $qEnd && $mEnd) {
            $overlaps = $qStart->lessThanOrEqualTo($mEnd) && $mStart->lessThanOrEqualTo($qEnd);
            if ($overlaps) {
                return 0.40;
            }
        }

        // Memory falls within query range (memory is a point inside query range)
        if ($qStart && $qEnd && $mStart) {
            $contained = $mStart->greaterThanOrEqualTo($qStart) && $mStart->lessThanOrEqualTo($qEnd);
            if ($contained) {
                return 0.40;
            }
        }

        // Recurring rule match
        if (($queryTemporal['kind'] ?? null) === 'recurring'
            && ($memoryTemporal['kind'] ?? null) === 'recurring'
            && ($queryTemporal['recurrence_rule'] ?? null) === ($memoryTemporal['recurrence_rule'] ?? null)) {
            return 0.40;
        }

        // Recurring memory matched against a recurring query (different rules)
        if (($queryClassification['is_recurring'] ?? false)
            && ($memoryTemporal['kind'] ?? null) === 'recurring') {
            return 0.25;
        }

        // Label overlap
        $qLabel = mb_strtolower((string) ($queryTemporal['label'] ?? ''), 'UTF-8');
        $mLabel = mb_strtolower((string) ($memoryTemporal['label'] ?? ''), 'UTF-8');

        if ($qLabel !== '' && $mLabel !== '' && str_contains($mLabel, $qLabel)) {
            return 0.20;
        }

        // Both schedule-like
        if (($queryTemporal['schedule_like'] ?? false) && ($memoryTemporal['schedule_like'] ?? false)) {
            return 0.10;
        }

        // Both have temporal but no specific match
        return 0.05;
    }

    private function parseTemporalDate(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {return null;}

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {return null;}
    }

    // ═════════════════════════════════════════════════════════════
    //  Prompt formatting — temporal-aware
    // ═════════════════════════════════════════════════════════════

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
        $meta = is_array($memory->metadata) ? $memory->metadata : [];
        $temporal = is_array($meta['temporal'] ?? null) ? $meta['temporal'] : [];

        $line = sprintf(
            '[%s] %s',
            strtoupper((string) $memory->type),
            trim((string) $memory->content)
        );

        if (! ($temporal['has_temporal'] ?? false)) {
            return $line;
        }

        $tz = $temporal['timezone'] ?? config('app.timezone');
        $kind = $temporal['kind'] ?? null;
        $sourcePhrase = $temporal['source_phrase'] ?? null;

        // ── Recurring: show recurrence rule ──────────────────────
        if ($kind === 'recurring' && filled($temporal['recurrence_rule'] ?? null)) {
            $humanRule = $this->humanizeRecurrenceRule($temporal['recurrence_rule']);
            $line .= ' — ' . $humanRule;

            if (filled($sourcePhrase)) {
                $line .= ' (from "' . $sourcePhrase . '")';
            }

            return $line;
        }

        // ── Date-based: show resolved date or range ─────────────
        if (filled($temporal['start_at'] ?? null)) {
            try {
                $startDate = CarbonImmutable::parse($temporal['start_at'], $tz);
                $endDate = filled($temporal['end_at'] ?? null)
                    ? CarbonImmutable::parse($temporal['end_at'], $tz)
                    : null;

                // Single day or point-in-time
                if (! $endDate || $startDate->isSameDay($endDate)) {
                    $hasTime = ($startDate->hour !== 0 || $startDate->minute !== 0);
                    $line .= ' — ' . $startDate->format($hasTime ? 'd M Y H:i' : 'd M Y');
                } else {
                    // Date range
                    $line .= ' — ' . $startDate->format('d M Y') . ' → ' . $endDate->format('d M Y');
                }

                if (filled($sourcePhrase)) {
                    $line .= ' (resolved from "' . $sourcePhrase . '")';
                }
            } catch (\Throwable) {
                // keep original line
            }
        }

        return $line;
    }

    /**
     * Convert an RRULE string into a human-readable phrase.
     *
     * E.g., "FREQ=WEEKLY;BYDAY=MO,WE,FR" → "Every Mon, Wed, Fri (weekly)"
     */
    private function humanizeRecurrenceRule(string $rrule): string
    {
        $parts = [];
        foreach (explode(';', $rrule) as $segment) {
            $kv = explode('=', $segment, 2);
            if (count($kv) === 2) {
                $parts[strtoupper($kv[0])] = $kv[1];
            }
        }

        $freq = $parts['FREQ'] ?? null;
        $interval = (int) ($parts['INTERVAL'] ?? 1);
        $byDay = $parts['BYDAY'] ?? null;

        $dayMap = [
            'MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu',
            'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun',
        ];

        $freqLabel = match ($freq) {
            'DAILY'     => $interval > 1 ? "Every {$interval} days" : 'Daily',
            'WEEKLY'    => $interval > 1 ? "Every {$interval} weeks" : 'Weekly',
            'MONTHLY'   => $interval > 1 ? "Every {$interval} months" : 'Monthly',
            'YEARLY'    => $interval > 1 ? "Every {$interval} years" : 'Yearly',
            'QUARTERLY' => 'Quarterly',
            'HALF-YEARLY' => 'Every 6 months',
            default     => $rrule,
        };

        if ($byDay) {
            $days = array_map(
                fn (string $d) => $dayMap[trim($d)] ?? trim($d),
                explode(',', $byDay)
            );
            return 'Every ' . implode(', ', $days) . ' (' . mb_strtolower($freqLabel) . ')';
        }

        return $freqLabel;
    }

    // ═════════════════════════════════════════════════════════════
    //  Utility helpers
    // ═════════════════════════════════════════════════════════════

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