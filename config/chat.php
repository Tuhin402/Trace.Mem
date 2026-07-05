<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flag — Kill Switch
    |--------------------------------------------------------------------------
    |
    | Set CHAT_ENDPOINT_ENABLED=false to immediately return 503 on every
    | POST /api/v1/chat request. All other endpoints are completely unaffected.
    | No redeployment required — just change the env var and cache config.
    |
    */
    'enabled' => (bool) env('CHAT_ENDPOINT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Memory Classifier Mode
    |--------------------------------------------------------------------------
    |
    | Controls how TraceMem decides whether a message should be stored.
    |
    |  hybrid     — heuristic regex first; NIM LLM only for ambiguous messages (default)
    |  heuristic  — regex only; NIM classifier never called
    |  disabled   — classifier skipped entirely; memory_mode governs storage
    |
    */
    'classifier' => env('CHAT_MEMORY_CLASSIFIER', 'hybrid'),

    /*
    |--------------------------------------------------------------------------
    | Classifier Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | When the NIM classifier returns remember=true, this threshold must also
    | be met. If confidence < threshold, the message is NOT stored.
    | Range: 0.0–1.0. Default: 0.75.
    |
    */
    'classifier_confidence' => (float) env('CHAT_CLASSIFIER_CONFIDENCE_THRESHOLD', 0.75),

    /*
    |--------------------------------------------------------------------------
    | Context Token Budget (internal)
    |--------------------------------------------------------------------------
    |
    | Maximum tokens allocated to assembled memory context injected into the
    | system prompt. Not exposed as a public API field (kept internal for now).
    | Range: 64–2000. Default: 800.
    |
    */
    'context_token_budget' => (int) env('CHAT_CONTEXT_TOKEN_BUDGET', 800),

    /*
    |--------------------------------------------------------------------------
    | Idempotency TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) an idempotency key is remembered.
    | If the same key is received within this window, the cached response is
    | returned immediately with no additional NIM calls or DB writes.
    | Default: 300 seconds (5 minutes).
    |
    */
    'idempotency_ttl' => (int) env('CHAT_IDEMPOTENCY_TTL_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | Chat-Specific Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Separate, stricter rate limit applied to /chat only (in addition to the
    | per-key limit applied by ApiKeyAuthMiddleware for all endpoints).
    |
    | rate_limit_max    — requests allowed in the window
    | rate_limit_window — window duration in seconds
    |
    | Default: 2 requests per second.
    |
    */
    'rate_limit_max'    => (int) env('CHAT_RATE_LIMIT_MAX', 2),
    'rate_limit_window' => (int) env('CHAT_RATE_LIMIT_WINDOW', 1),

    /*
    |--------------------------------------------------------------------------
    | Prompt Version
    |--------------------------------------------------------------------------
    |
    | Tracks which classifier / system-prompt template is active.
    | Surfaced in the debug block (debug=true requests only) to aid
    | A/B testing and future prompt upgrades.
    |
    */
    'prompt_version' => env('CHAT_PROMPT_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker — NIM Classifier
    |--------------------------------------------------------------------------
    |
    | If the NIM classifier fails consecutively >= failure_threshold times,
    | the circuit opens and all subsequent classifier calls return immediately
    | with remember=false (heuristic-only fallback) until the recovery window
    | elapses. This prevents cascading latency when NVIDIA has a bad hour.
    |
    */
    'circuit_failure_threshold' => (int) env('CHAT_CIRCUIT_FAILURE_THRESHOLD', 5),
    'circuit_recovery_seconds'  => (int) env('CHAT_CIRCUIT_RECOVERY_SECONDS', 60),

];
