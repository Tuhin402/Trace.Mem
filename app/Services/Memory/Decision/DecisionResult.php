<?php

namespace App\Services\Memory\Decision;

/**
 * Immutable value object representing the full output of MemoryDecisionEngine::decide().
 *
 * Fields
 * ──────
 *  remember       — Whether the message should be stored.
 *  type           — Memory type: fact | preference | rule | skill | null
 *  confidence     — 0.0–1.0. Deterministic: min(matched_weight / threshold_weight, 1.0)
 *  matchedRules   — IDs of rules that fired, in evaluation order.
 *  weights        — Parallel array of weights for each matched rule (for explainability).
 *  reason         — Human-readable string. May change between engine versions.
 *  reasonCode     — Machine-stable constant from DecisionReasonCode. NEVER changes.
 *  via            — How the decision was reached: rule_engine | forced | disabled |
 *                   code_skip | negative_rule | skip_pattern
 *  ruleVersion    — config('memory_rules.rule_version')
 *  engineVersion  — config('memory_rules.engine_version')
 *  volatility     — Internal: 'persistent' | 'volatile'. Not exposed via API.
 *                   Used by future decay engine.
 *  evaluatedRules — Full trace of every rule tested (for /debug/memory-decision).
 *                   Empty array in production mode (never logged to keep overhead low).
 *  elapsedUs      — Microseconds spent in the engine (for benchmark assertions).
 */
final class DecisionResult
{
    /**
     * @param string[]                                $matchedRules
     * @param int[]                                   $weights
     * @param array<array{id:string, matched:bool}>   $evaluatedRules
     */
    public function __construct(
        public readonly bool    $remember,
        public readonly ?string $type,
        public readonly float   $confidence,
        public readonly array   $matchedRules,
        public readonly array   $weights,
        public readonly string  $reason,
        public readonly string  $reasonCode,
        public readonly string  $via,
        public readonly int     $ruleVersion,
        public readonly int     $engineVersion,
        public readonly string  $volatility,
        public readonly array   $evaluatedRules = [],
        public readonly int     $elapsedUs      = 0,
    ) {}

    /**
     * Serialize to array.
     *
     * The public-facing keys (remember, type, reason, via, confidence) are
     * shape-identical to the old HybridClassifierService output, preserving
     * full backward compatibility for ChatOrchestrationService consumers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'remember'       => $this->remember,
            'type'           => $this->type,
            'confidence'     => $this->confidence,
            'matched_rules'  => $this->matchedRules,
            'reason'         => $this->reason,
            'reason_code'    => $this->reasonCode,
            'via'            => $this->via,
            'rule_version'   => $this->ruleVersion,
            'engine_version' => $this->engineVersion,
        ];
    }

    /**
     * Full explainability payload for /debug/memory-decision.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return array_merge($this->toArray(), [
            'volatility'     => $this->volatility,
            'weights'        => $this->weights,
            'evaluated_rules'=> $this->evaluatedRules,
            'elapsed_us'     => $this->elapsedUs,
        ]);
    }

    /**
     * Metadata array to be merged into stored memory records.
     *
     * @return array<string, mixed>
     */
    public function toMemoryMetadata(): array
    {
        return [
            'engine_version' => $this->engineVersion,
            'rule_version'   => $this->ruleVersion,
            'matched_rule'   => $this->matchedRules[0] ?? null,
            'matched_rules'  => $this->matchedRules,
            'reason_code'    => $this->reasonCode,
            'volatility'     => $this->volatility,
            'confidence'     => $this->confidence,
            'via'            => $this->via,
        ];
    }
}
