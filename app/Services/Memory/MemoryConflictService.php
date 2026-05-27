<?php

namespace App\Services\Memory;

use App\Models\Memory;

class MemoryConflictService
{
    private const CONFLICT_THRESHOLD = 0.65;

    public function __construct(
        private readonly MemorySemanticSegmentationService $semanticSegmenter,
    ) {}

    /**
     * Resolve possible conflicts for the given memory.
     */
    public function resolve(Memory $memory): void
    {
        $others = Memory::query()
            ->where('tenant_id', $memory->tenant_id)
            ->where('user_id', $memory->user_id)
            ->where('type', $memory->type)
            ->where('id', '!=', $memory->id)
            ->latest()
            ->limit(10)
            ->get();

        foreach ($others as $other) {
            /** @var Memory $other */
            $analysis = $this->analyzeConflict(
                $memory->normalized_content,
                $other->normalized_content,
                $memory->type
            );

            if (! $analysis['conflicts']) {
                continue;
            }

            $meta = is_array($other->metadata) ? $other->metadata : [];

            $meta['conflicts_with'] = array_values(array_unique(array_merge(
                $meta['conflicts_with'] ?? [],
                [$memory->id]
            )));

            $meta['conflict_score'] = max(
                (float) ($meta['conflict_score'] ?? 0),
                $analysis['score']
            );

            $meta['conflict_reasons'] = array_values(array_unique(array_merge(
                $meta['conflict_reasons'] ?? [],
                $analysis['reasons']
            )));

            $meta['last_conflict_at'] = now()->toISOString();

            $other->metadata = $meta;
            $other->confidence = round(max(0.1, ((float) $other->confidence - (0.15 * $analysis['score']))), 4);
            $other->decay_score = round(max(0.1, ((float) $other->decay_score - (0.10 * $analysis['score']))), 4);
            $other->save();
        }
    }

    /**
     * Preview conflicts for incoming content without mutating stored memories.
     */
    public function preview(
        string $tenantId,
        string $userId,
        string $content
    ): array {
        $segments = $this->semanticSegmenter->split($content);
    
        if (empty($segments)) {
            return [
                'total_conflicts' => 0,
                'segments' => [],
            ];
        }
    
        $storedMemories = Memory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereIn(
                'type',
                MemorySemanticSegmentationService::ALLOWED_TYPES
            )
            ->latest()
            ->limit(50)
            ->get();
    
        $segmentResults = [];
    
        foreach ($segments as $segment) {
            $normalizedIncoming = $this->normalizeText(
                $segment['content']
            );
    
            if ($normalizedIncoming === '') {
                continue;
            }
    
            $conflicts = [];
    
            foreach ($storedMemories as $memory) {
                /** @var Memory $memory */
    
                $analysis = $this->analyzeConflict(
                    $normalizedIncoming,
                    (string) $memory->normalized_content,
                    (string) $memory->type
                );
    
                if (! $analysis['conflicts']) {
                    continue;
                }
    
                $conflicts[] = [
                    'memory_id' => $memory->id,
                    'stored_type' => $memory->type,
                    'stored_content' => $memory->content,
                    'stored_normalized_content' => $memory->normalized_content,
    
                    'incoming_type' => $segment['type'],
                    'incoming_content' => $segment['content'],
    
                    'score' => round($analysis['score'], 4),
    
                    'severity' => $this->severityLevel(
                        $analysis['score']
                    ),
    
                    'reasons' => $analysis['reasons'],
    
                    'created_at' => $memory->created_at,
                    'updated_at' => $memory->updated_at,
                ];
            }
    
            usort(
                $conflicts,
                fn (array $a, array $b): int =>
                    $b['score'] <=> $a['score']
            );
    
            $segmentResults[] = [
                'segment' => $segment,
                'total_conflicts' => count($conflicts),
                'conflicts' => $conflicts,
            ];
        }
    
        return [
            'total_segments' => count($segmentResults),
    
            'total_conflicts' => collect($segmentResults)
                ->sum('total_conflicts'),
    
            'segments' => $segmentResults,
        ];
    }

    private function severityLevel(float $score): string
    {
        return match (true) {
            $score >= 0.9 => 'critical',
            $score >= 0.75 => 'high',
            $score >= 0.5 => 'medium',
            default => 'low',
        };
    }

    /**
     * Return a conflict decision plus score and reasons.
     */
    private function analyzeConflict(string $a, string $b, string $type): array
    {
        if (! in_array($type, ['preference', 'rule', 'fact',], true)) {
            return [
                'conflicts' => false,
                'score' => 0.0,
                'reasons' => [],
            ];
        }

        if (trim($a) === '' || trim($b) === '') {
            return [
                'conflicts' => false,
                'score' => 0.0,
                'reasons' => [],
            ];
        }

        $aTokens = $this->tokens($a);
        $bTokens = $this->tokens($b);

        if (empty($aTokens) || empty($bTokens)) {
            return [
                'conflicts' => false,
                'score' => 0.0,
                'reasons' => [],
            ];
        }

        $reasons = [];
        $score = 0.0;

        // Exact normalized match is not a conflict.
        if ($this->normalizeText($a) === $this->normalizeText($b)) {
            return [
                'conflicts' => false,
                'score' => 0.0,
                'reasons' => [],
            ];
        }

        // Direct antonym / opposite-pair detection.
        $pairHit = $this->detectOpposingPairs($a, $b);
        if ($pairHit !== null) {
            $score += 0.65;
            $reasons[] = 'opposing_pair:' . $pairHit;
        }

        // Negation mismatch + shared context is a strong signal.
        $negA = $this->hasNegation($a);
        $negB = $this->hasNegation($b);

        if ($negA !== $negB) {
            $shared = $this->sharedKeywordsCount($aTokens, $bTokens);

            if ($shared >= 2) {
                $score += 0.45;
                $reasons[] = 'negation_mismatch_shared_context';
            }
        }

        // Semantic-ish overlap: same topic, but different key words.
        $overlap = $this->keywordOverlapRatio($aTokens, $bTokens);
        if ($overlap >= 0.35) {
            $score += 0.20;
            $reasons[] = 'topic_overlap';
        }

        // Additional cues that often indicate a real rule/preference conflict.
        $directionalHit = $this->detectDirectionalConflict($a, $b);
        if ($directionalHit !== null) {
            $score += 0.35;
            $reasons[] = 'directional:' . $directionalHit;
        }

        // Lightweight “meaning” check: same topic with opposing intent.
        if ($this->sharesKeywords($aTokens, $bTokens) && $this->hasOpposingIntent($a, $b)) {
            $score += 0.20;
            $reasons[] = 'opposing_intent';
        }

        $score = round(min(1.0, $score), 4);

        return [
            'conflicts' => $score >= self::CONFLICT_THRESHOLD,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    /**
     * Unicode-safe normalization for comparisons.
     */
    private function normalizeText(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?? $text;
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * Unicode-safe tokenization with stop-word removal.
     */
    private function tokens(string $text): array
    {
        $text = $this->normalizeText($text);

        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $text) ?: [];

        $stopWords = [
            'the', 'is', 'a', 'an', 'and', 'or', 'to', 'of', 'in', 'on', 'for', 'with',
            'at', 'by', 'from', 'as', 'it', 'this', 'that', 'these', 'those', 'i',
            'you', 'he', 'she', 'we', 'they', 'me', 'my', 'your', 'our', 'their',
            'be', 'been', 'are', 'was', 'were', 'am', 'do', 'does', 'did',
        ];

        $words = array_filter($words, fn ($word) => $word !== '' && ! in_array($word, $stopWords, true));

        return array_values(array_unique($words));
    }

    private function hasNegation(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');

        return str_contains($text, 'never')
            || str_contains($text, ' not ')
            || str_contains($text, "don't")
            || str_contains($text, 'dont')
            || str_contains($text, 'avoid')
            || str_contains($text, 'cannot')
            || str_contains($text, "can't")
            || str_contains($text, 'no ');
    }

    /**
     * Returns matched opposing pair label, or null.
     */
    private function detectOpposingPairs(string $a, string $b): ?string
    {
        $a = mb_strtolower($a, 'UTF-8');
        $b = mb_strtolower($b, 'UTF-8');

        $pairs = [
            // Quantitative and Qualitative
            ['short', 'long'],
            ['concise', 'detailed'],
            ['small', 'large'],
            ['tiny', 'huge'],
            ['simple', 'complicated'],
            ['simple', 'advanced'],
            ['simple', 'complex'],
            ['easy', 'hard'],
            ['easy', 'difficult'],
            ['low', 'high'],
            ['minimum', 'maximum'],
            ['up', 'down'],
            ['increase', 'decrease'],
            ['less', 'more'],
            ['few', 'many'],
            ['slow', 'fast'],
            ['light', 'heavy'],
            ['light', 'dark'],
            ['early', 'late'],
            ['cheap', 'expensive'],
            ['cheap', 'costly'],
            ['formal', 'casual'],

            // Binary States / Booleans 
            ['yes', 'no'],
            ['yeah', 'nah'],
            ['true', 'false'],
            ['on', 'off'],
            ['enabled', 'disabled'],
            ['enable', 'disable'],
            ['start', 'stop'],
            ['active', 'inactive'],
            ['present', 'absent'],
            ['available', 'unavailable'],
            ['allow', 'deny'],
            ['allow', 'block'],
            ['allow', 'disallow'],
            ['accept', 'decline'],
            ['accept', 'reject'],

            // Qualities or Opinions
            ['good', 'bad'],
            ['safe', 'dangerous'],
            ['win', 'lose'],
            ['winner', 'loser'],
            ['old', 'new'],
            ['young', 'old'],
            ['hot', 'cold'],
            ['like', 'dislike'],
            ['love', 'hate'],
            ['prefer', 'avoid'],

            // Preferences and Intent
            ['want', "don't want"],
            ['want', 'do not want'],
            ['want', 'dont want'],
            ['need', "don't need"],
            ['need', 'do not need'],
            ['need', 'dont need'],
            ['use', "don't use"],
            ['use', 'do not use'],
            ['use', 'dont use'],
            ['require', "don't require"],
            ['require', 'do not require'],
            ['require', 'forbid'],
            ['support', 'oppose'],
            ['support', "don't support"],
            ['support', 'do not support'],
            ['choose', "don't choose"],
            ['choose', 'do not choose'],
            ['accept', "don't accept"],
            ['accept', 'do not accept'],

            // Modality, Obligation
            ['always', 'never'],
            ['must', 'must not'],
            ['have to', "don't have to"],
            ['should', 'should not'],
            ['should', "shouldn't"],
            ['must', "mustn't"],
            ['mandatory', 'optional'],
            ['obligatory', 'voluntary'],
            ['permit', 'prohibit'],
            ['can', 'cannot'],
            ['can', "can't"],
            ['may', 'may not'],
            ['allowed', 'not allowed'],
            ['permitted', 'not permitted'],
            ['required', 'not required'],
            ['necessary', 'unnecessary'],

            // Expertise and Experience
            ['beginner', 'expert'],
            ['novice', 'expert'],
            ['amateur', 'professional'],
            ['junior', 'senior'],

            // Visibility and Scope
            ['public', 'private'],
            ['visible', 'hidden'],
            ['open', 'closed'],
            ['show', 'hide'],
            ['show', "don't show"],
        ];

        foreach ($pairs as [$left, $right]) {
            $leftInA = str_contains($a, $left);
            $rightInA = str_contains($a, $right);
            $leftInB = str_contains($b, $left);
            $rightInB = str_contains($b, $right);

            if (($leftInA && $rightInB) || ($rightInA && $leftInB)) {
                return $left . '_' . $right;
            }
        }

        return null;
    }

    /**
     * Returns a directional conflict label when phrases point opposite ways.
     */
    private function detectDirectionalConflict(string $a, string $b): ?string
    {
        $a = mb_strtolower($a, 'UTF-8');
        $b = mb_strtolower($b, 'UTF-8');

        $patterns = [
            // Expression of desire or attitude
            ['i like', 'i dislike'],
            ['i love', 'i hate'],
            ['i agree', 'i disagree'],
            ['i support', 'i oppose'],
            ['i enjoy', 'i detest'],
            ['i accept', 'i refuse'],
            ['i approve', 'i disapprove'],
            ['i want', "i don't want"],
            ['i need', "i don't need"],
            ['i wish', "i don't wish"],
            ['i prefer', "i do not prefer"],
            ['i prefer', "i prefer not to"],
            ['i care about', "i don't care about"],

            // Directive/behavioral
            ['should', "should not"],
            ['must', "must not"],
            ['can', "cannot"],
            ['should always', "should never"],
            ['always', 'never'],
            ['allowed', 'not allowed'],
            ['permitted', 'not permitted'],
            ['required', 'not required'],
            ['mandatory', 'optional'],
            ['necessary', 'unnecessary'],
            ['obligatory', 'voluntary'],

            // Action vs. Inaction
            ['use', "do not use"],
            ['use', "don't use"],
            ['choose', "do not choose"],
            ['choose', "don't choose"],
            ['allow', "do not allow"],
            ['accept', "do not accept"],
            ['recommend', "do not recommend"],
            ['suggest', "do not suggest"],

            // Accessibility/visibility
            ['make public', 'keep private'],
            ['make visible', 'keep hidden'],
            ['show', 'hide'],
            ['enable', 'disable'],

            // Simple natural pairs
            ['yes', 'no'],
            ['agree', 'disagree'],
            ['support', 'oppose'],
            ['favor', 'reject'],
        ];

        foreach ($patterns as [$left, $right]) {
            if (
                (str_contains($a, $left) && str_contains($b, $right)) ||
                (str_contains($a, $right) && str_contains($b, $left))
            ) {
                return $left . '_' . $right;
            }
        }

        return null;
    }

    private function hasOpposingIntent(string $a, string $b): bool
    {
        $a = mb_strtolower($a, 'UTF-8');
        $b = mb_strtolower($b, 'UTF-8');

        $intentWords = [
            'prefer', 'like', 'love', 'want', 'wish', 'would like', 'desire', 'favor',
            'keen on', 'fond of', 'inclined to', 'open to', 'choose', 'interested in',
            'enjoy', 'support', 'approve', 'recommend', 'suggest', 'accept', 'welcome',
            'appreciate', 'allow', 'encourage', 'endorse', 'value', 'am for', 'promote',
            'have a positive view of', 'see as beneficial', 'okay with', 'comfortable with', 'consent',
            'happy to', 'eager to', 'intend to', 'intend on', 'would rather', 'look forward to'
        ];

        $opposeWords = [
            'avoid', 'dislike', 'hate', 'not', 'never', 'should not', 'must not', 'do not',
            "don't", "doesn't", "didn't", "can't", 'cannot', 'no', 'refuse', 'reject', 'resist',
            'object to', 'oppose', 'against', 'ban', 'forbid', 'block', 'prohibit', 'restrict',
            'decline', 'dismiss', 'exclude', 'discourage', 'disapprove', 'negative about',
            'not okay with', 'not comfortable with', 'not interested', 'not allow', 'no way',
            'unwilling', 'hesitant to', 'reluctant to', 'refuse to', 'am not for', 'wish to avoid'
        ];

        $aHasIntent = $this->containsAny($a, $intentWords);
        $bHasOppose = $this->containsAny($b, $opposeWords);

        $aHasOppose = $this->containsAny($a, $opposeWords);
        $bHasIntent = $this->containsAny($b, $intentWords);

        return ($aHasIntent && $bHasOppose) || ($aHasOppose && $bHasIntent);
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function sharedKeywordsCount(array $aTokens, array $bTokens): int
    {
        return count(array_intersect($aTokens, $bTokens));
    }

    private function keywordOverlapRatio(array $aTokens, array $bTokens): float
    {
        $shared = $this->sharedKeywordsCount($aTokens, $bTokens);
        $total = max(count($aTokens), count($bTokens), 1);

        return round($shared / $total, 4);
    }

    private function sharesKeywords(array $aTokens, array $bTokens): bool
    {
        return $this->sharedKeywordsCount($aTokens, $bTokens) >= 2;
    }
}