<?php

namespace App\Services\Memory;

use App\Services\Memory\MemoryTemporalService;

class MemorySemanticSegmentationService
{
    public function __construct(
        private readonly MemoryTemporalService $temporalService,
    ) {}

    public const ALLOWED_TYPES = ['preference', 'fact', 'rule', 'skill',];

    private const PREFERENCE_PATTERNS = [
        'love' => 1.0,
        'hate' => 1.0,
        'obsessed with' => 1.0,
        'absolutely love' => 1.0,

        'prefer' => 0.95,
        'favorite' => 0.95,
        'favourite' => 0.95,
        'gravitate towards' => 0.92,

        'like' => 0.8,
        'enjoy' => 0.8,
        'appreciate' => 0.8,
        'go for' => 0.75,

        'interested in' => 0.7,
        'partial to' => 0.7,
        'usually pick' => 0.65,
        'usually go with' => 0.6,
        'all about' => 0.65,

        "can't live without" => 0.9,
        'tend to like' => 0.65,
        'rather not' => 0.6,
        "it's not my thing" => 0.5,
        "find myself wanting" => 0.7,
        "have strong feelings about" => 0.8,
        'my go-to' => 0.7,
        'it\'s my go-to' => 0.7,
        'generally avoid' => 0.45,
        'go-to' => 0.7,
        'keen on' => 0.7,
        'fond of' => 0.75,
        'dislike' => 0.7,
        'want' => 0.5,
        'have a soft spot for' => 0.8,

        'tend to enjoy' => 0.5,
        'often choose' => 0.5,
        'i admire' => 0.5,
        'i appreciate' => 0.6,
        'tend to avoid' => 0.3,
        'prefer not to' => 0.4,
        'tend to dislike' => 0.4,
        'drawn to' => 0.35,
        'not a fan of' => 0.2,
    ];

    private const RULE_PATTERNS = [
        'must always'         => 1.0,
        'must not'            => 1.0,
        'never'               => 1.0,
        'forbidden'           => 1.0,
        'mandatory'           => 1.0,
        'prohibited from'     => 1.0,
        'obligatory'          => 0.95,
        'mandated to'         => 0.95,

        'must'                => 0.9,
        'required to'         => 0.9,
        'mandatory to'        => 0.9,
        'not allowed to'      => 0.9,
        'do not'              => 0.9,
        'don\'t'              => 0.9,

        "can't"               => 0.85,
        'cannot'              => 0.85,
        'not permitted to'    => 0.85,
        'supposed not to'     => 0.85,
        'against the rules'   => 0.85,

        'should always'       => 0.8,
        'should not'          => 0.8,
        'need to'             => 0.8,
        'discouraged from'    => 0.8,

        'should'              => 0.6,
        'expected to'         => 0.6,
        'supposed to'         => 0.55,
        'expected that i'     => 0.5,

        'encouraged to'       => 0.4,
        'duty to'             => 0.4,
        'a rule that'         => 0.4,

        'make it a rule to'   => 0.3,
        'always'              => 0.2,
    ];

    private const SKILL_PATTERNS = [
        'expertise in'      => 1.0,
        'specialize in'     => 1.0,
        'mastered'          => 1.0,
        'experience with'   => 0.95,
        'experienced in'    => 0.9,
        'proficient with'   => 0.9,
        'certified in'      => 0.9,
        'background is in'  => 0.9,
        'trained in'        => 0.88,

        'skilled at'        => 0.8,
        'great at'          => 0.75,
        'good at'           => 0.7,
        'assist with'       => 0.7,
        'practiced'         => 0.7,
        'familiar with'     => 0.6,
        'learned'           => 0.6,
        'know how to'       => 0.6,
        'able to'           => 0.6,
        'comfortable with'  => 0.6,
        'competent at'      => 0.6,
        'understand how to' => 0.6,

        'can'               => 0.4,
        'built'             => 0.4,
        'knowledge in'      => 0.4,
        'know a lot about'  => 0.3,
    ];

    public function split(string $input): array
    {
        $pipeline = new MemorySemanticSegmentationPipeline();

        $segments = $pipeline->split($input);

        // ── Detect global intent markers from the raw input ──────────
        $inputLower               = mb_strtolower($input, 'UTF-8');
        $isTransient              = $this->detectTransient($inputLower);
        $hasExplicitRemember      = $this->detectExplicitRemember($inputLower);
        $hasExplicitRememberCode  = $this->detectExplicitRememberCode($inputLower);

        $memories = [];

        foreach ($segments as $segment) {
            $text = trim((string) ($segment['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $metadata = is_array($segment['metadata'] ?? null)
                ? $segment['metadata']
                : [];

            $sourceKind = $metadata['source_kind'] ?? 'plain';

            $type = $sourceKind === 'code_snippet'
                ? 'skill'
                : $this->detectType($text);

            $content = $sourceKind === 'code_snippet'
                ? ($metadata['raw_excerpt'] ?? $text)
                : $this->normalizeContent($text);

            $confidence = $this->confidenceScore($text, $type);

            // ── Subject-based confidence adjustments ─────────────────
            if (($metadata['subject'] ?? 'self') === 'other') {
                $confidence = max(0.1, round($confidence - 0.12, 4));
            } elseif (($metadata['subject'] ?? 'self') === 'general') {
                $confidence = max(0.1, round($confidence - 0.05, 4));
            }

            // ── Transient instruction handling ───────────────────────
            if ($isTransient) {
                $metadata['transient'] = true;
                $confidence = min($confidence, 0.35);
            }

            // ── Explicit remember handling ───────────────────────────
            if ($hasExplicitRemember) {
                $metadata['explicit_remember'] = true;
            }

            // ── Code-gating: skip raw code unless explicitly asked ───
            if ($sourceKind === 'code_snippet') {
                if ($hasExplicitRememberCode) {
                    $metadata['explicit_remember'] = true;
                    $metadata['skip_storage'] = false;
                } else {
                    $metadata['skip_storage'] = true;
                }
            }

            // ── Temporal Memory Store ────────────────────────────────
            [$metadata, $confidence] = $this->enrichTemporalMetadata($text, $metadata, $confidence);

            $memories[] = [
                'type'       => $type,
                'content'    => $content,
                'confidence' => $confidence,
                'metadata'   => $metadata,
            ];
        }

        return array_values($memories);
    }

    private function enrichTemporalMetadata(string $text, array $metadata, float $confidence): array
    {
        $temporal = $this->temporalService->extract($text);

        $metadata['temporal'] = $temporal;
        $metadata['memory_kind'] = ($temporal['has_temporal'] ?? false)
            ? ($temporal['kind'] ?? 'time_reference')
            : ($metadata['memory_kind'] ?? 'note');

        if (($temporal['has_temporal'] ?? false) && $confidence < 0.9) {
            $confidence = min(1.0, round($confidence + 0.05, 4));
        }

        return [$metadata, $confidence];
    }

    public function detectSemanticType(string $text): string
    {
        return $this->detectType($text);
    }

    public static function isAllowedType(string $type): bool
    {
        return in_array(
            strtolower(trim($type)),
            self::ALLOWED_TYPES,
            true
        );
    }

    private function normalizeAtomicMemory(string $text): ?array
    {
        $normalized = trim($text);

        if ($normalized === '') {
            return null;
        }

        $type = $this->detectType($normalized);

        return [
            'type' => $type,
            'content' => $this->normalizeContent($normalized),
            'confidence' => $this->confidenceScore($normalized, $type),
        ];
    }
    
    private function detectType(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        $scores = [
            'preference' => $this->calculatePatternScore($lower, self::PREFERENCE_PATTERNS),
            'rule' => $this->calculatePatternScore($lower, self::RULE_PATTERNS),
            'skill' => $this->calculatePatternScore($lower, self::SKILL_PATTERNS),
            'fact' => 0.1,
        ];

        arsort($scores);

        return array_key_first($scores);
    }

    private function normalizeContent(string $text): string
    {
        $text = trim($text);

        $replacements = [
            '/^i like /iu' => 'User likes ',
            '/^i really like /iu' => 'User really likes ',
            '/^i don\'t like /iu' => 'User dislikes ',
            '/^i do not like /iu' => 'User dislikes ',
            '/^i dislike /iu' => 'User dislikes ',
            '/^i love /iu' => 'User loves ',
            '/^i absolutely love /iu' => 'User absolutely loves ',
            '/^i hate /iu' => 'User hates ',
            '/^i really hate /iu' => 'User really hates ',
            '/^i enjoy /iu' => 'User enjoys ',
            '/^i really /iu' => 'User really ',
            '/^i usually /iu' => 'User usually ',
            '/^i use /iu' => 'User use ',
            '/^i uses /iu' => 'User uses ',
            '/^i used /iu' => 'User used ',
            '/^i just /iu' => 'User just ',
            '/^i need /iu' => 'User needs ',
            '/^i needs /iu' => 'User needs ',
            '/^i prefer /iu' => 'User prefers ',
            '/^i would rather /iu' => 'User would rather ',
            '/^i\'d rather /iu' => 'User would rather ',
            '/^my favorite /iu' => 'User\'s favorite ',
            '/^my favourite /iu' => 'User\'s favourite ',
            '/^i tend to like /iu' => 'User tends to like ',
            '/^i tend to /iu' => 'User tends to ',
            '/^i usually like /iu' => 'User usually likes ',
            '/^i mostly like /iu' => 'User mostly likes ',
            '/^i always choose /iu' => 'User always chooses ',
            '/^i never /iu' => 'User never ',

            '/^i want /iu' => 'User wants ',
            '/^i really want /iu' => 'User really wants ',
            '/^i wish /iu' => 'User wishes ',
            '/^i would like /iu' => 'User would like ',

            '/^i can /iu' => 'User can ',
            '/^i cannot /iu' => 'User cannot ',
            '/^i can\'t /iu' => 'User cannot ',
            '/^i\'m able to /iu' => 'User is able to ',
            '/^i am able to /iu' => 'User is able to ',
            '/^i know how to /iu' => 'User knows how to ',
            '/^i know /iu' => 'User knows ',
            '/^i have experience with /iu' => 'User has experience with ',
            '/^i have experience in /iu' => 'User has experience in ',
            '/^i am experienced in /iu' => 'User is experienced in ',
            '/^i am experienced with /iu' => 'User is experienced with ',
            '/^i am skilled in /iu' => 'User is skilled in ',
            '/^i am skilled at /iu' => 'User is skilled at ',
            '/^i work with /iu' => 'User works with ',
            '/^i built /iu' => 'User built ',
            '/^i have built /iu' => 'User has built ',
            '/^i am certified in /iu' => 'User is certified in ',
            '/^i specialize in /iu' => 'User specializes in ',
            '/^i am good at /iu' => 'User is good at ',
            '/^i am great at /iu' => 'User is great at ',
            '/^i know a lot about /iu' => 'User knows a lot about ',

            '/^i must /iu' => 'User must ',
            '/^i have to /iu' => 'User has to ',
            '/^i am required to /iu' => 'User is required to ',
            '/^i need to /iu' => 'User needs to ',
            '/^i should /iu' => 'User should ',
            '/^i am supposed to /iu' => 'User is supposed to ',
            '/^i\'m supposed to /iu' => 'User is supposed to ',
            '/^i am not supposed to /iu' => 'User is not supposed to ',
            '/^i\'m not supposed to /iu' => 'User is not supposed to ',
            '/^i can not /iu' => 'User cannot ',
            '/^i not allowed to /iu' => 'User is not allowed to ',
            '/^i\'m not allowed to /iu' => 'User is not allowed to ',
            '/^i shouldn\'t /iu' => 'User should not ',
            '/^i should not /iu' => 'User should not ',
            '/^i must not /iu' => 'User must not ',

            '/^i am /iu' => 'User is ',
            '/^i\'m /iu' => 'User is ',
            '/^i was /iu' => 'User was ',
            '/^i have /iu' => 'User has ',
            '/^i had /iu' => 'User had ',
            '/^i\'ve /iu' => 'User has ',
            '/^i\'d /iu' => 'User would ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        $text = preg_replace('/^i don\'?t like /iu', 'User dislikes ', $text);
        $text = preg_replace('/^i do not like /iu', 'User dislikes ', $text);

        $text = preg_replace('/^i can\'?t stand /iu', 'User hates ', $text);
        $text = preg_replace('/^i cannot stand /iu', 'User hates ', $text);
        $text = preg_replace('/^i cannot tolerate /iu', 'User hates ', $text);

        $text = rtrim($text, ".!? ");

        return trim($text);
    }

    private function confidenceScore(string $text, string $type): float
    {
        $score = 0.55;

        $lower = mb_strtolower($text, 'UTF-8');

        if (preg_match('/\bi\b/u', $lower)) {
            $score += 0.10;
        }

        if ($type !== 'fact') {
            $score += 0.10;
        }

        if (mb_strlen($text, 'UTF-8') > 20) {
            $score += 0.05;
        }

        return round(min($score, 1.0), 4);
    }

    private function normalizeText(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);

            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function calculatePatternScore(string $text, array $patterns): float
    {
        $score = 0.0;

        foreach ($patterns as $pattern => $weight) {
            if (preg_match('/\b' . preg_quote($pattern, '/') . '\b/u', $text)) {
                $score += $weight;
            }
        }

        return round($score, 4);
    }

    // ─────────────────────────────────────────────────────────────
    //  Global intent detection — Transient markers
    // ─────────────────────────────────────────────────────────────

    private function detectTransient(string $lowerInput): bool
    {
        $phrases = [
            // ── Explicit task / session / conversation scope ─────────
            'for this task only',
            'for this task',
            'this task only',
            'for this session only',
            'for this session',
            'for this conversation only',
            'for this conversation',
            'for this chat only',
            'for this chat',
            'for this request only',
            'for this request',
            'for this query only',
            'for this prompt only',
            'for this project only',
            'for this thread only',
            'for this thread',

            // ── "Just / only" + temporal qualifier ──────────────────
            'just for now',
            'just for today',
            'just for tonight',
            'just for this',
            'just this once',
            'just this time',
            'just this one time',
            'just temporarily',
            'only for now',
            'only for today',
            'only for tonight',
            'only this time',
            'only this once',
            'only right now',
            'only today',
            'only tonight',

            // ── Direct temporal markers ─────────────────────────────
            'right now only',
            'for now',
            'for the moment',
            'for the time being',
            'at the moment only',
            'at this point only',
            'in this case only',
            'in this instance',
            'this time only',
            'today only',
            'tonight only',
            'one-time only',
            'one time only',
            'one-off',

            // ── Explicit non-permanence ─────────────────────────────
            'temporarily',
            'temporary',
            'temp only',
            'not permanent',
            'not permanently',
            'not forever',
            'short-term',
            'short term only',
            'provisional',
            'provisionally',
            'interim',
            'ephemeral',

            // ── "Don't save / remember" directives ──────────────────
            "don't remember this",
            'do not remember this',
            "don't save this",
            'do not save this',
            "don't store this",
            'do not store this',
            "don't keep this",
            'do not keep this',
            'forget this after',
            'forget after this',
            'discard after',
            'discard this after',
            "don't memorize",
            'do not memorize',
            'no need to remember',
            'no need to save',
            'not worth remembering',
            'not worth saving',

            // ── Implied short-lived intent ──────────────────────────
            'for a bit',
            'for a moment',
            'for a second',
            'for a minute',
            'for the next few minutes',
            'for the next hour',
            'for the next few hours',
            'for the current task',
            'during this task',
            'during this session',
            'while working on this',
            'while i work on this',
            'until i finish this',
            'until this is done',
            "until i'm done",
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($lowerInput, $phrase)) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────
    //  Global intent detection — Explicit remember markers
    // ─────────────────────────────────────────────────────────────

    private function detectExplicitRemember(string $lowerInput): bool
    {
        $phrases = [
            // ── Direct remember / save / keep ────────────────────────
            'remember this',
            'remember that',
            'remember the following',
            'remember what i',
            'save this',
            'save that',
            'save the following',
            'keep this',
            'keep that',
            'keep the following',
            'store this',
            'store that',
            'store the following',

            // ── Negation of forgetting ──────────────────────────────
            "don't forget",
            'do not forget',
            'never forget',
            "don't lose this",
            'do not lose this',

            // ── Imperative memory phrases ────────────────────────────
            'memorize this',
            'memorize that',
            'note this',
            'note that',
            'take note',
            'make a note',
            'make note of',
            'jot this down',
            'write this down',
            'log this',
            'record this',

            // ── Cognitive / mental directives ────────────────────────
            'keep in mind',
            'bear in mind',
            'keep this in mind',
            'bear this in mind',
            'commit to memory',
            'commit this to memory',

            // ── Polite / request forms ──────────────────────────────
            'please remember',
            'please save',
            'please store',
            'please keep',
            'please note',
            'i want you to remember',
            'i need you to remember',
            'i want you to save',
            'i need you to save',
            'can you remember',
            'could you remember',
            'can you save',
            'could you save',

            // ── Later / future retrieval ────────────────────────────
            'save for later',
            'keep for later',
            'store for later',
            'save for future',
            'keep for future',
            'remember for later',
            'remember for next time',
            'save for next time',

            // ── Misc memory-related ─────────────────────────────────
            'pin this',
            'bookmark this',
            'hold onto this',
            'preserve this',
            'retain this',
            'hold this',
            'file this',
            'stash this',
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($lowerInput, $phrase)) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────
    //  Global intent detection — Explicit remember-code markers
    // ─────────────────────────────────────────────────────────────

    private function detectExplicitRememberCode(string $lowerInput): bool
    {
        $phrases = [
            // ── "remember / save / store / keep" + code entity ───────
            'remember this code',
            'remember the code',
            'remember this snippet',
            'remember the snippet',
            'remember this script',
            'remember this function',
            'remember this class',
            'remember this method',
            'remember this block',
            'remember this implementation',
            'remember this solution',
            'remember this fix',
            'remember this bugfix',
            'remember this bug fix',
            'remember this pattern',
            'remember this example',
            'remember this syntax',
            'remember this template',
            'remember this config',
            'remember this configuration',
            'remember this query',
            'remember this command',
            'remember this regex',
            'remember this algorithm',
            'remember this logic',
            'remember this approach',
            'remember this workaround',
            'remember this hack',
            'remember this trick',

            'save this code',
            'save the code',
            'save this snippet',
            'save the snippet',
            'save this script',
            'save this function',
            'save this implementation',
            'save this solution',
            'save this fix',
            'save this bugfix',
            'save this bug fix',
            'save this pattern',
            'save this example',
            'save this syntax',
            'save this template',
            'save this config',
            'save this command',
            'save this query',
            'save this regex',

            'store this code',
            'store the code',
            'store this snippet',
            'store the snippet',
            'store this script',
            'store this function',
            'store this implementation',

            'keep this code',
            'keep the code',
            'keep this snippet',
            'keep the snippet',
            'keep this script',
            'keep this function',
            'keep this implementation',

            'memorize this code',
            'memorize this snippet',

            // ── "note / pin / bookmark" + code ──────────────────────
            'note this code',
            'note this snippet',
            'pin this code',
            'pin this snippet',
            'bookmark this code',
            'bookmark this snippet',
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($lowerInput, $phrase)) {
                return true;
            }
        }

        return false;
    }
}