<?php

namespace App\Services;

use App\Services\Memory\MemorySemanticSegmentationService;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Iqbalatma\LaravelServiceRepo\BaseService;

class MemoryExtractionService extends BaseService
{
    public function __construct(
        private readonly MemorySemanticSegmentationService $semanticSegmenter
    ) {}

    public function extract(string $input): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 300)
                ->withToken(config('services.openai.api_key'))
                ->post(config('services.openai.base_url') . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => '
                                You are a memory extraction engine.

                                Your task:
                                - Extract atomic user memories from the input.
                                - Split compound sentences into separate memories.
                                - Each memory must contain ONLY ONE idea.
                                - Return ONLY valid JSON array.
                                - Do not explain anything.

                                Allowed types:
                                - preference
                                - fact
                                - rule
                                - skill

                                Examples:

                                Input: "I like React, I can build Laravel APIs, and I hate overly verbose responses"
                                Output:
                                [
                                    {
                                        "type": "preference",
                                        "content": "User likes React"
                                    },
                                    {
                                        "type": "skill",
                                        "content": "User can build Laravel APIs"
                                    },
                                    {
                                        "type": "preference",
                                        "content": "User hates overly verbose responses"
                                    }
                                ]

                                Input: "I can build Laravel APIs"
                                Output:
                                [
                                    {
                                        "type": "skill",
                                        "content": "User can build Laravel APIs"
                                    }
                                ]
                            ',
                        ],
                        [
                            'role' => 'user',
                            'content' => $input,
                        ],
                    ],
                    'temperature' => 0.2,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'memory_extraction',
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'memories' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'type' => [
                                                    'type' => 'string'
                                                ],
                                                'content' => [
                                                    'type' => 'string'
                                                ]
                                            ],
                                            'required' => ['type', 'content'],
                                            'additionalProperties' => false
                                        ]
                                    ]
                                ],
                                'required' => ['memories'],
                                'additionalProperties' => false
                            ]
                        ]
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI extraction failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackExtract($input);
            }

            $content = $response->json('choices.0.message.content');

            $decoded = json_decode($content, true);
            Log::info('OpenAI raw response', [
                'response' => $response->json(),
            ]);

            if (
                ! is_array($decoded) ||
                ! isset($decoded['memories']) ||
                ! is_array($decoded['memories'])
            ) {
                return $this->fallbackExtract($input);
            }

            return $this->sanitizeExtractedMemories(
                $decoded['memories'],
                $input
            );
        } catch (Throwable $e) {
            Log::error('OpenAI extraction exception', [
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackExtract($input);
        }
    }

    private function sanitizeExtractedMemories(array $items, string $input): array
    {
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = strtolower(trim($item['type'] ?? ''));
            $content = trim($item['content'] ?? '');

            if ($content === '') {
                continue;
            }

            if (! $this->semanticSegmenter->isAllowedType($type)) {
                $type = $this->semanticSegmenter->detectSemanticType($content);
            }

            // Build consistent metadata shape for AI-extracted items
            $isCodeHeavy = $this->isCodeHeavyContent($content);

            $metadata = [
                'source_kind'   => $isCodeHeavy ? 'code_snippet' : 'plain',
                'contains_code' => $isCodeHeavy,
                'subject'       => 'general',
                'source'        => 'ai_extraction',
            ];

            // Code-heavy AI output is gated the same way as pipeline code
            if ($isCodeHeavy) {
                $metadata['skip_storage'] = true;
            }

            $result[] = [
                'type'       => $type,
                'content'    => $content,
                'confidence' => 0.7,
                'metadata'   => $metadata,
            ];
        }

        return $result ?: $this->fallbackExtract($input);
    }

    private function fallbackExtract(string $input): array
    {
        return $this->semanticSegmenter->split($input);
    }

    /**
     * Detect whether content is primarily code (fenced blocks, dense backticks,
     * or high ratio of syntax-heavy characters).
     */
    private function isCodeHeavyContent(string $content): bool
    {
        // Fenced code blocks
        if (preg_match('/```[\s\S]*?```/u', $content)) {
            return true;
        }

        // Dense inline backtick usage (4+ backtick characters)
        if (substr_count($content, '`') >= 4) {
            return true;
        }

        // High ratio of syntax-heavy characters typical of code
        $totalChars = mb_strlen($content, 'UTF-8');

        if ($totalChars > 20) {
            $specialChars = preg_match_all('/[{}()\[\];=><!@#$%^&*~]/u', $content);

            if ($specialChars !== false && ($specialChars / $totalChars) > 0.15) {
                return true;
            }
        }

        return false;
    }
}