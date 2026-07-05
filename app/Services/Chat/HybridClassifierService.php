<?php

namespace App\Services\Chat;

use App\Services\NimClient;
use Illuminate\Support\Facades\Log;
use Throwable;


/**
 * Hybrid memory classifier.
 *
 * Decision tree (in order):
 *
 *  memory_mode = "force"   → remember: true  (no NIM call)
 *  memory_mode = "off"     → remember: false (no NIM call)
 *  classifier  = "disabled" → remember: false (no NIM call)
 *  heuristic SKIP match    → remember: false (no NIM call)
 *  heuristic STORE match   → remember: true  (no NIM call)
 *  circuit breaker OPEN    → remember: false (no NIM call)
 *  classifier = "heuristic" → remember: false (no NIM call)
 *  NIM call (temp=0, deterministic) → parsed and threshold-checked
 *
 * The NIM classifier is therefore called ONLY for ambiguous messages
 * that match neither a SKIP nor a STORE heuristic pattern, AND only
 * when the circuit is closed. In practice this covers ~20-30% of
 * messages; the rest are decided in microseconds.
 */
class HybridClassifierService
{
    // ── Heuristic skip patterns ───────────────────────────────────────────────
    // Messages matching any of these are never stored.

    private const SKIP_PATTERNS = [
        '/^(hi|hello|hey|yo|sup|howdy|good\s+(morning|afternoon|evening|night)|how are you)/i',
        '/^[\d\s\+\-\*\/\^%=\(\)\.]+[=?\s]*$/',          // pure math
        '/tell\s+(me\s+)?a\s+joke|make\s+me\s+laugh/i',
        '/what.{0,15}(weather|temperature|forecast)/i',
        '/translate\s+(this|it|that|the\s+\w+)/i',
        '/^(write|generate|create|make|build|give me|show me|explain|describe|list|summarise|summarize|define)\b/i',
        '/^(what is|what are|who is|who are|how does|how do|why does|why do|when did|where is)\b/i',
        '/^(who won|what happened|latest news|score of|result of)\b/i',
        '/^[a-z\s\!\.\,\?\-]{1,20}$/i',                  // very short, no substance
    ];

    // ── Heuristic store patterns ──────────────────────────────────────────────
    // Messages matching any of these are always stored (no NIM call).

    private static array $storePatterns = [
        [
            'pattern' => "/\b(my name is|i['’]m called|call me)\b/i",
            'type'    => 'fact',
        ],
        [
            'pattern' => "/\b(i (live|stay|am based) in|i['’]m from|i work in)\b/i",
            'type'    => 'fact',
        ],
        [
            'pattern' => "/\b(i (like|love|prefer|enjoy|use|rely on|always use))\b/i",
            'type'    => 'preference',
        ],
        [
            'pattern' => "/\b(i (hate|dislike|avoid|never use|can['’]t stand|don['’]t like))\b/i",
            'type'    => 'preference',
        ],
        [
            'pattern' => "/\bmy (favourite|favorite|preferred|go-to|main)\b/i",
            'type'    => 'preference',
        ],
        [
            'pattern' => "/^(always|never)\s+(use|write|respond|answer|include|avoid|add|give)\b/i",
            'type'    => 'rule',
        ],
        [
            'pattern' => "/\bi['’]m (allergic|vegetarian|vegan|diabetic|lactose[- ]intolerant|gluten)\b/i",
            'type'    => 'fact',
        ],
        [
            'pattern' => "/\bi (can|know how to|am able to|have (built|made|written|created))\b/i",
            'type'    => 'skill',
        ],
        [
            'pattern' => "/\b(i work (at|for|as)|my (job|role|title|position) is)\b/i",
            'type'    => 'fact',
        ],
        [
            'pattern' => "/\bi['’]m (a|an) (developer|engineer|designer|student|teacher|doctor|nurse)\b/i",
            'type'    => 'fact',
        ],
    ];

    // ── Classifier prompt (prompt_version: v1) ────────────────────────────────
    private const CLASSIFIER_SYSTEM_PROMPT = <<<'PROMPT'
You are a memory classifier. A user message is given. Decide if it describes a lasting personal fact about the user that should be stored as long-term memory.
Return ONLY valid JSON — no explanation, no markdown, no preamble, no trailing text.

{"remember":true,"type":"preference","reason":"one short sentence"}
or
{"remember":false,"type":null,"reason":"one short sentence"}

Allowed type values: preference, fact, rule, skill

STORE if the message contains: user preferences, personal facts about the user, personal rules the user applies, user skills or abilities.
DO NOT STORE: questions, greetings, requests to write or generate code, math, weather queries, news, jokes, translations, general knowledge questions, one-off tasks.
PROMPT;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly NimClient      $nim,
    ) {}

    /**
     * Classify a message.
     *
     * @return array{remember: bool, type: string|null, reason: string, confidence: float, via: string}
     */
    public function classify(string $message, string $mode = 'auto'): array
    {
        // ── memory_mode shortcuts — bypass classifier entirely ────────────────
        if ($mode === 'force') {
            return $this->result(true, 'fact', 'Forced by memory_mode=force', 1.0, 'forced');
        }

        if ($mode === 'off') {
            return $this->result(false, null, 'Disabled by memory_mode=off', 1.0, 'disabled');
        }

        // ── classifier disabled globally ──────────────────────────────────────
        if (config('chat.classifier', 'hybrid') === 'disabled') {
            return $this->result(false, null, 'Classifier disabled via config', 1.0, 'disabled');
        }

        // ── heuristic pass ────────────────────────────────────────────────────
        $heuristic = $this->heuristicClassify(trim($message));

        if ($heuristic !== null) {
            return $heuristic;
        }

        // ── heuristic-only mode: no ambiguous NIM call ────────────────────────
        if (config('chat.classifier', 'hybrid') === 'heuristic') {
            return $this->result(false, null, 'Ambiguous message; LLM classifier disabled', 0.5, 'heuristic');
        }

        // ── circuit breaker ───────────────────────────────────────────────────
        if ($this->circuitBreaker->isOpen()) {
            return $this->result(false, null, 'Classifier circuit open; heuristic-only fallback active', 0.0, 'circuit_open');
        }

        // ── NIM LLM classifier ────────────────────────────────────────────────
        return $this->llmClassify($message);
    }

    /** Expose circuit state for logging and debug blocks. */
    public function getCircuitState(): string
    {
        return $this->circuitBreaker->getState();
    }

    // ── Private — heuristic ───────────────────────────────────────────────────

    private function heuristicClassify(string $message): ?array
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $message)) {
                return $this->result(false, null, 'Matched skip pattern', 0.95, 'heuristic');
            }
        }

        foreach (self::$storePatterns as $rule) {
            if (preg_match($rule['pattern'], $message)) {
                return $this->result(true, $rule['type'], 'Matched personal information pattern', 0.9, 'heuristic');
            }
        }

        return null; // ambiguous — caller should try LLM
    }

    // ── Private — NIM classifier ──────────────────────────────────────────────

    private function llmClassify(string $message): array
    {
        try {
            $response = $this->nim->completions([
                'model'       => config('services.nvidia_nim_openai.model', 'openai/gpt-oss-20b'),
                'temperature' => 0,    // deterministic — classification must not be creative
                'top_p'       => 1,
                'max_tokens'  => 100,
                'stream'      => false,
                'messages'    => [
                    ['role' => 'system', 'content' => self::CLASSIFIER_SYSTEM_PROMPT],
                    ['role' => 'user',   'content' => $message],
                ],
            ], timeout: 15);

            if (! $response->successful()) {
                $this->circuitBreaker->recordFailure();
                return $this->result(false, null, 'Classifier API returned ' . $response->status(), 0.0, 'nvidia');
            }

            $this->circuitBreaker->recordSuccess();

            $raw    = (string) $response->json('choices.0.message.content', '');
            $parsed = $this->safeParseJson($raw);

            if ($parsed === null) {
                return $this->result(false, null, 'Classifier returned unparseable JSON; defaulting to no-remember', 0.0, 'parse_error');
            }

            $remember   = (bool) ($parsed['remember'] ?? false);
            $type       = $this->sanitizeType($parsed['type'] ?? null);
            $reason     = isset($parsed['reason']) ? substr((string) $parsed['reason'], 0, 200) : 'LLM classification';
            $confidence = (float) min(1.0, max(0.0, $parsed['confidence'] ?? 0.5));
            $threshold  = (float) config('chat.classifier_confidence', 0.75);

            if ($remember && $confidence < $threshold) {
                return $this->result(
                    false, $type,
                    "Confidence {$confidence} below threshold {$threshold}; not storing",
                    $confidence, 'nvidia'
                );
            }

            return $this->result($remember, $type, $reason, $confidence, 'nvidia');

        } catch (Throwable $e) {
            $this->circuitBreaker->recordFailure();
            return $this->result(false, null, 'Classifier exception; defaulting to no-remember', 0.0, 'classifier_error');
        }
    }

    // ── Private — helpers ─────────────────────────────────────────────────────

    /**
     * Safely decode JSON from a classifier response.
     * Handles leading prose, code-fences, and malformed output.
     * Never throws — returns null on any failure.
     */
    private function safeParseJson(string $content): ?array
    {
        $content = trim($content);

        // Strip markdown fences if present
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $content = trim($content);

        // Direct decode
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract first JSON object (handles "Sure! {...}")
        if (preg_match('/\{[^{}]+\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function sanitizeType(?string $type): ?string
    {
        $allowed = ['preference', 'fact', 'rule', 'skill'];
        $type    = strtolower(trim((string) $type));
        return in_array($type, $allowed, true) ? $type : 'fact';
    }

    /** @return array{remember: bool, type: string|null, reason: string, confidence: float, via: string} */
    private function result(bool $remember, ?string $type, string $reason, float $confidence, string $via): array
    {
        return compact('remember', 'type', 'reason', 'confidence', 'via');
    }
}
