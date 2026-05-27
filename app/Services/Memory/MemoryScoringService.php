<?php

namespace App\Services\Memory;

use App\Models\Memory;

class MemoryScoringService
{
    private const STRONG_OBLIGATION_WORDS = [
        'always', 'must', 'have to', 'should', 'need to', 'ought to', 'required', 'mandatory', 'required to', 'supposed to', 'expected to', 'obliged', 'have got to', 'has to', 'necessary', 'cannot skip', 'can’t skip', 'absolutely must', 'under obligation', 'absolutely have to',
    ];

    private const PROHIBITION_WORDS = [
        'never', 'cannot', "can't", 'not allowed', 'not permitted', 'not supposed to', 'prohibited', 'forbidden', 'avoid', 'disable', 'do not', 'should not', 'must not', 'cannot do', "don’t", "don\'t", "won't", 'no way', 'under no circumstances', 'restricted', 'banned', 'barred', "outlawed", "disallowed", "off limits"
    ];

    private const IMPORTANCE_WORDS = [
        'important', 'crucial', 'critical', 'essential', 'vital', 'key', 'significant', 'noteworthy', 'main', 'major', 'imperative', 'paramount', 'necessary', 'pressing', 'meaningful', 'weighty', 'principal', 'indispensable', 'pivotal', 'substantial', 'valuable', 'big deal', 'urgent', 'take seriously', 'matters most'
    ];

    private const HIGH_INTENT_WORDS = [
        'prefer', 'like', 'love', 'want', 'need', 'use', 'allow', 'accept', 'choose', 'would like', 'wish', 'desire', 'favor', 'keen on', 'looking for', 'yearn for', 'go for', 'lean towards', 'appreciate', 'value', 'enjoy', 'would rather', 'interested in'
    ];

    private const STOP_WORDS = [
        'the', 'is', 'a', 'an', 'and', 'or', 'to', 'of', 'in', 'on', 'for', 'with',
        'at', 'by', 'from', 'as', 'it', 'this', 'that', 'these', 'those', 'i',
        'you', 'he', 'she', 'we', 'they', 'me', 'my', 'your', 'our', 'their',
        'be', 'been', 'are', 'was', 'were', 'am', 'do', 'does', 'did',
    ];

    public function baseImportance(string $type, string $content): float
    {
        $base = match ($type) {
            'rule' => 0.9,
            'fact' => 0.8,
            'preference' => 0.7,
            'skill' => 0.6,
            default => 0.5,
        };

        $text = $this->normalizeText($content);
        $tokens = $this->tokens($text);

        if ($this->containsAny($text, self::STRONG_OBLIGATION_WORDS)) {
            $base += 0.10;
        }

        if ($this->containsAny($text, self::PROHIBITION_WORDS)) {
            $base += 0.15;
        }

        if ($this->containsAny($text, self::IMPORTANCE_WORDS)) {
            $base += 0.20;
        }

        if ($this->containsAny($text, self::HIGH_INTENT_WORDS)) {
            $base += 0.05;
        }

        if ($type === 'fact' && preg_match('/\b\d{1,4}([\/\-:.]\d{1,4})*\b/u', $text)) {
            $base += 0.05;
        }

        if (count($tokens) >= 12) {
            $base += 0.05;
        }

        if (mb_strlen($text, 'UTF-8') > 100) {
            $base += 0.05;
        }

        return round(min($base, 1.0), 4);
    }

    public function recallScore(Memory $memory): float
    {
        return round(
            ((float) $memory->importance * 0.5) +
            ((float) $memory->confidence * 0.3) +
            ((float) $memory->decay_score * 0.2),
            4
        );
    }

    public function decayScore(Memory $memory): float
    {
        $anchor = $memory->last_accessed_at ?? $memory->updated_at ?? now();
        $days = now()->diffInDays($anchor);

        $score = exp(-0.05 * $days);

        return round(max(0.1, min($score, 1.0)), 4);
    }

    private function normalizeText(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function tokens(string $text): array
    {
        $parts = preg_split('/\s+/u', $text) ?: [];

        $parts = array_filter($parts, function (string $word): bool {
            return $word !== '' && ! in_array($word, self::STOP_WORDS, true);
        });

        return array_values(array_unique($parts));
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
}