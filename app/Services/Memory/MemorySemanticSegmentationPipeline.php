<?php

namespace App\Services\Memory;

class MemorySemanticSegmentationPipeline
{
    private const DEFAULT_MAX_SEGMENTS = 40;

    private CodeDetectionService $codeDetector;

    public function __construct(?CodeDetectionService $codeDetector = null)
    {
        $this->codeDetector = $codeDetector ?? new CodeDetectionService();
    }

    public function split(string $input, int $maxSegments = self::DEFAULT_MAX_SEGMENTS): array
    {
        $input = $this->normalizeInput($input);

        if (trim($input) === '') {
            return [];
        }

        [$maskedInput, $maskMap] = $this->maskProtectedRegions($input);
        $blocks = $this->extractBlocks($maskedInput);

        $segments = [];

        foreach ($blocks as $block) {
            $blockText = trim($this->restoreProtectedRegions($block['text'], $maskMap));

            if ($blockText === '') {
                continue;
            }

            $blockHasExplicitCode = $block['contains_code'];

            $delimiter = $block['delimiter'] ?? $this->inferDelimiter($blockText);

            $pieces = $block['kind'] === 'code'
                ? [$block['text']]
                : $this->splitBlockIntoPieces($block['text']);

            foreach ($pieces as $piece) {
                $restored = trim($this->restoreProtectedRegions($piece, $maskMap));

                if ($restored === '') {
                    continue;
                }

                $pieceHasCode = $blockHasExplicitCode || $this->codeDetector->isCodeHeavy($restored);
                
                $pieceSourceKind = $this->detectSourceKind(
                    $block['kind'],
                    $pieceHasCode
                );

                $segments[] = [
                    'text' => $restored,
                    'metadata' => [
                        'subject' => $pieceSourceKind === 'code_snippet'
                            ? 'general'
                            : $this->detectSubject($restored),

                        'source_kind' => $pieceSourceKind,
                        'contains_code' => $pieceHasCode,
                        'delimiter' => $delimiter,
                        'raw_excerpt' => $this->excerpt($restored),
                    ],
                ];

                if (count($segments) >= $maxSegments) {
                    return $segments;
                }
            }
        }

        return $segments;
    }

    private function normalizeInput(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);

            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function maskProtectedRegions(string $text): array
    {
        $map = [];
        $counter = 0;

        $patterns = [
            'CODE' => '/```(?:[\w-]+)?\s*[\s\S]*?```/u',
            'INLINE' => '/`[^`]+`/u',
            'QUOTE' => '/"(?:\\\\.|[^"\\\\])*"/u',
            'SMARTQUOTE' => '/“(?:\\\\.|[^”\\\\])*”/u',
            'URL' => '~(?:https?://|www\.)[^\s<>"\'`]+~iu',
        ];

        foreach ($patterns as $prefix => $pattern) {
            $text = preg_replace_callback($pattern, function (array $matches) use (&$map, &$counter, $prefix) {
                $token = "__TM_{$prefix}_{$counter}__";
                $map[$token] = $matches[0];
                $counter++;

                return $token;
            }, $text) ?? $text;
        }

        return [$text, $map];
    }

    private function restoreProtectedRegions(string $text, array $map): string
    {
        return strtr($text, $map);
    }

    private function extractBlocks(string $text): array
    {
        $text = preg_replace('/\R/u', "\n", $text) ?? $text;
        $paragraphs = preg_split('/\n{2,}/u', trim($text)) ?: [];

        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            $lines = preg_split('/\n/u', $paragraph) ?: [];
            $buffer = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if ($this->isCodeLine($line)) {
                    if ($buffer !== []) {
                        $blocks[] = $this->makeBlock(implode(' ', $buffer), 'plain', 'paragraph');
                        $buffer = [];
                    }

                    $blocks[] = $this->makeBlock($line, 'code', 'code_block');
                    continue;
                }

                if ($this->isMarkdownBoundaryLine($line)) {
                    if ($buffer !== []) {
                        $blocks[] = $this->makeBlock(implode(' ', $buffer), 'plain', 'paragraph');
                        $buffer = [];
                    }

                    $blocks[] = $this->makeBlock(
                        $this->stripMarkdownMarker($line),
                        'markdown',
                        $this->delimiterForMarkdownLine($line)
                    );

                    continue;
                }

                $buffer[] = $line;
            }

            if ($buffer !== []) {
                $blocks[] = $this->makeBlock(implode(' ', $buffer), 'plain', 'paragraph');
            }
        }

        return $blocks;
    }

    private function makeBlock(string $text, string $kind, string $delimiter): array
    {
        return [
            'text' => $text,
            'kind' => $kind,
            'delimiter' => $delimiter,
            'contains_code' => $this->containsCode($text),
        ];
    }

    private function isCodeLine(string $line): bool
    {
        return (bool) preg_match('/^__TM_(?:CODE|INLINE)_\d+__$/u', trim($line));
    }

    private function isMarkdownBoundaryLine(string $line): bool
    {
        return (bool) preg_match(
            '/^\s{0,3}(?:#{1,6}\s+|>\s+|[-*+•‣◦]\s+|\d{1,3}[.)]\s+)/u',
            $line
        );
    }

    private function stripMarkdownMarker(string $line): string
    {
        $line = preg_replace('/^\s{0,3}#{1,6}\s+/u', '', $line) ?? $line;
        $line = preg_replace('/^\s*>\s+/u', '', $line) ?? $line;
        $line = preg_replace('/^\s*(?:[-*+•‣◦]|\d{1,3}[.)])\s+/u', '', $line) ?? $line;

        return trim($line);
    }

    private function delimiterForMarkdownLine(string $line): string
    {
        if (preg_match('/^\s{0,3}#{1,6}\s+/u', $line)) {
            return 'heading';
        }

        if (preg_match('/^\s*>\s+/u', $line)) {
            return 'quote';
        }

        if (preg_match('/^\s*(?:\d{1,3}[.)])\s+/u', $line)) {
            return 'numbered';
        }

        return 'bullet';
    }

    private function splitBlockIntoPieces(string $text): array
    {
        $pattern = '/(?:\s*[•‣◦]\s*|\s*\.\.\.+\s*|\s*[;|]\s*|\s*(?<!\d):\s*(?!\d)\s*|\s*(?<![A-Za-z]:)[\/\\\\]\s*|\s*,\s*|\s+\b(?:and|but|while|although)\b\s+|\s*(?<=[.!?])\s+)/iu';

        $pieces = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $pieces)));
    }

    private function detectSourceKind(string $kind, bool $containsCode): string
    {
        if ($kind === 'code') {
            return 'code_snippet';
        }

        if ($containsCode && $kind === 'markdown') {
            return 'mixed';
        }

        if ($containsCode && $kind === 'plain') {
            return 'mixed';
        }

        if ($kind === 'markdown') {
            return 'markdown';
        }

        return 'plain';
    }

    private function detectSubject(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        if (preg_match('/\b(?:my|our)\s+(?:gf|girlfriend|bf|boyfriend|wife|husband|partner|mother|mom|father|dad|brother|sister|son|daughter|friend|coworker|colleague|teammate|roommate|neighbor|neighbour|boss|manager|ex)\b/u', $lower)) {
            return 'other';
        }

        if (preg_match('/\b(?:he|she|they|them|their|theirs|his|her|hers|someone|somebody|another person|that person|this person)\b/u', $lower)) {
            return 'other';
        }

        if (preg_match('/\b(?:i|me|my|mine|myself|we|us|our|ours|ourselves)\b/u', $lower)) {
            return 'self';
        }

        return 'general';
    }

    private function containsCode(string $text): bool
    {
        return str_contains($text, '__TM_CODE_') || str_contains($text, '__TM_INLINE_');
    }

    private function inferDelimiter(string $text): string
    {
        if (preg_match('/\.\.\.+/u', $text)) {
            return 'ellipsis';
        }

        if (preg_match('/\|/u', $text)) {
            return 'pipe';
        }

        if (preg_match('/;/u', $text)) {
            return 'semicolon';
        }

        if (preg_match('/,/u', $text)) {
            return 'comma';
        }

        if (preg_match('/(?<![A-Za-z]:)[\/\\\\]/u', $text)) {
            return 'slash';
        }

        if (preg_match('/(?<!\d)\.(?!\d)|[!?]/u', $text)) {
            return 'sentence';
        }

        return 'paragraph';
    }

    private function excerpt(string $text, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit, 'UTF-8') . '…';
    }
}