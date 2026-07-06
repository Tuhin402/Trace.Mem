<?php

namespace App\Services\Memory\Decision;

/**
 * Immutable value object encoding the context in which a decision is made.
 *
 * Passed into MemoryDecisionEngine::decide() alongside the raw message.
 * Makes context-awareness contractual, explicit, and testable.
 *
 * Fields
 * ──────
 *  endpoint    — Which API endpoint triggered the decision.
 *                Only 'chat' invokes the engine; 'remember' always stores directly.
 *  memoryMode  — 'auto' | 'force' | 'off'
 *  isDryRun    — Preview only — engine evaluates but no writes occur.
 *  userAgent   — Forwarded for future heuristics (analytics, bot detection).
 *                Optional; no rules currently use it.
 */
final class DecisionContext
{
    public const ENDPOINT_CHAT    = 'chat';
    public const ENDPOINT_REMEMBER = 'remember';

    public function __construct(
        public readonly string  $endpoint   = self::ENDPOINT_CHAT,
        public readonly string  $memoryMode = 'auto',
        public readonly bool    $isDryRun   = false,
        public readonly ?string $userAgent  = null,
    ) {}

    public function isForced(): bool
    {
        return $this->memoryMode === 'force';
    }

    public function isDisabled(): bool
    {
        return $this->memoryMode === 'off';
    }

    public function isAuto(): bool
    {
        return $this->memoryMode === 'auto';
    }
}
