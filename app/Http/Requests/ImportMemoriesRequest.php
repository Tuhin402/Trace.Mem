<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * ImportMemoriesRequest
 *
 * Performs strict, layered validation of the JSON memory import file before
 * any data reaches the controller. Guards against:
 *
 *   - Oversized uploads (7 MB hard cap at both Laravel and PHP level)
 *   - Malformed or non-UTF-8 JSON
 *   - Control characters embedded in content (null bytes, ASCII 0–8, 11–31)
 *   - Exceeding maximum memory count (500 per import)
 *   - Deeply nested JSON structures (≤ 5 levels — prevents stack-overflow attacks)
 *   - Unknown top-level fields (strict schema)
 *   - Missing required per-memory fields
 *   - Duplicate content_hash values within the same upload (pre-dedup)
 *   - Unknown per-memory fields (strict allowlist)
 */
class ImportMemoriesRequest extends FormRequest
{
    /** Maximum file size in kilobytes (7 MB). */
    private const MAX_SIZE_KB = 7168;

    /** Maximum number of memories allowed in a single import. */
    private const MAX_MEMORIES = 500;

    /** Maximum allowed JSON nesting depth. */
    private const MAX_DEPTH = 5;

    /** Allowed top-level keys in the import JSON. */
    private const ALLOWED_TOP_LEVEL_KEYS = [
        'version',
        'export_date',
        'original_user_id',
        'original_tenant_scope_id',
        'memories',
    ];

    /** Allowed keys per memory object. */
    private const ALLOWED_MEMORY_KEYS = [
        'content',
        'normalized_content',
        'content_hash',
        'type',
        'importance',
        'confidence',
        'decay_score',
        'status',
        'access_count',
        'last_accessed_at',
        'last_reinforced_at',
        'archived_at',
        'created_at',
        'updated_at',
        'metadata',
    ];

    /** Valid memory types. */
    private const VALID_TYPES = ['preference', 'fact', 'rule', 'skill'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimetypes:application/json,text/plain',
                'max:' . self::MAX_SIZE_KB,
            ],
        ];
    }

    /**
     * After standard validation passes, run deep content-level validation.
     * Throws a validation exception (→ HTTP 422) on any violation.
     */
    public function passedValidation(): void
    {
        $file    = $this->file('file');
        $content = file_get_contents($file->getRealPath());

        // 1. UTF-8 validity — reject any file that is not valid UTF-8
        if (! mb_check_encoding($content, 'UTF-8')) {
            $this->failWith('file', 'The import file must be valid UTF-8 encoded text.');
        }

        // 2. Control character check — reject null bytes and ASCII control chars
        //    (0x00–0x08, 0x0B–0x1F) that have no place in clean JSON content.
        //    0x09 (tab), 0x0A (LF), 0x0D (CR) are legitimate JSON whitespace.
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
            $this->failWith('file', 'The import file contains invalid control characters.');
        }

        // 3. Nesting depth check — parse with a depth limit to prevent
        //    stack exhaustion from pathologically nested JSON.
        $data = json_decode($content, true, self::MAX_DEPTH + 1);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Could be a depth error or plain malformed JSON.
            $detail = json_last_error() === JSON_ERROR_DEPTH
                ? 'The JSON is nested too deeply (maximum ' . self::MAX_DEPTH . ' levels).'
                : 'The file is not valid JSON: ' . json_last_error_msg();
            $this->failWith('file', $detail);
        }

        // 4. Top-level structure check
        if (! is_array($data) || ! isset($data['memories']) || ! is_array($data['memories'])) {
            $this->failWith('file', 'Invalid structure. The JSON must contain a "memories" array.');
        }

        // 5. Unknown top-level fields
        $unknownTopLevel = array_diff(array_keys($data), self::ALLOWED_TOP_LEVEL_KEYS);
        if (! empty($unknownTopLevel)) {
            $this->failWith(
                'file',
                'Unknown top-level fields: ' . implode(', ', $unknownTopLevel) . '.'
            );
        }

        // 6. Memory count cap
        $count = count($data['memories']);
        if ($count > self::MAX_MEMORIES) {
            $this->failWith(
                'file',
                "Too many memories in this import ({$count}). Maximum is " . self::MAX_MEMORIES . '.'
            );
        }

        // 7. Per-memory validation + duplicate content_hash detection
        $seenHashes = [];
        foreach ($data['memories'] as $index => $memory) {
            if (! is_array($memory)) {
                $this->failWith('file', "Memory at index {$index} is not a valid object.");
            }

            // Required fields
            if (empty($memory['content_hash'])) {
                $this->failWith('file', "Memory at index {$index} is missing 'content_hash'.");
            }
            if (empty($memory['type'])) {
                $this->failWith('file', "Memory at index {$index} is missing 'type'.");
            }
            if (! in_array($memory['type'], self::VALID_TYPES, true)) {
                $this->failWith(
                    'file',
                    "Memory at index {$index} has an invalid 'type' (" . $memory['type'] . ').'
                );
            }

            // Duplicate content_hash detection within this upload
            $hash = (string) $memory['content_hash'];
            if (isset($seenHashes[$hash])) {
                $this->failWith(
                    'file',
                    "Duplicate content_hash found at index {$index} (also seen at index {$seenHashes[$hash]}). "
                    . 'Remove duplicate memories before importing.'
                );
            }
            $seenHashes[$hash] = $index;

            // Unknown per-memory fields
            $unknownFields = array_diff(array_keys($memory), self::ALLOWED_MEMORY_KEYS);
            if (! empty($unknownFields)) {
                $this->failWith(
                    'file',
                    "Memory at index {$index} contains unknown fields: " . implode(', ', $unknownFields) . '.'
                );
            }
        }

        // 8. Attach the decoded payload so the controller doesn't re-decode.
        $this->attributes->set('decoded_import', $data);
    }

    /**
     * Throw a 422 validation exception with a file-field error message.
     * This terminates execution just like $this->validate() would.
     *
     * @param  string  $field
     * @param  string  $message
     * @return never
     */
    private function failWith(string $field, string $message): void
    {
        throw new HttpResponseException(
            back()->withErrors([$field => $message])
        );
    }
}
