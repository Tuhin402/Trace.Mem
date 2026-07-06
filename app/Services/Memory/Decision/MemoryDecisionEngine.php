<?php

namespace App\Services\Memory\Decision;

use App\Services\Memory\CodeDetectionService;
use App\Services\Memory\MemoryNormalizationService;
use Illuminate\Support\Facades\Log;

/**
 * MemoryDecisionEngine — deterministic, pure-PHP memory decision service.
 *
 * Guarantees
 * ──────────
 *  - No HTTP requests. No external service calls. No AI.
 *  - No database lookups during rule evaluation.
 *  - No randomness. Same input always produces the same output.
 *  - Target: <5ms p95 for typical user messages.
 *  - LLM (NVIDIA/OpenAI/etc.) unavailability has ZERO effect on this service.
 *
 * Confidence algorithm (threshold-anchored, production-stable)
 * ─────────────────────────────────────────────────────────────
 *   confidence = min(matched_weight / threshold_weight, 1.0)
 *
 *   threshold_weight is a fixed config constant (default: 100).
 *   Adding new rules NEVER changes the denominator.
 *   Old confidence scores are unaffected by new rule additions.
 *
 * Evaluation pipeline (in order)
 * ────────────────────────────────
 *  1. Context shortcuts  — force/off modes bypass rule evaluation
 *  2. Normalization      — reuses MemoryNormalizationService
 *  3. Code detection     — reuses CodeDetectionService
 *  4. Rule evaluation    — sorted by priority; terminal rules halt on first match
 *  5. Confidence calc    — threshold-anchored
 *  6. Volatility         — internal persistent/volatile classification
 *  7. Near-miss logging  — 0.40 ≤ confidence < threshold → logged with message hash
 *  8. Telemetry          — Redis counters incremented (fire-and-forget)
 *
 * Dependencies
 * ────────────
 *  MemoryNormalizationService — existing, unchanged
 *  CodeDetectionService       — existing, unchanged
 *  MemoryRuleRegistry         — new singleton
 *  DecisionTelemetry          — new fire-and-forget Redis wrapper
 */
class MemoryDecisionEngine
{
    public function __construct(
        private readonly MemoryNormalizationService $normalizer,
        private readonly CodeDetectionService       $codeDetection,
        private readonly MemoryRuleRegistry         $registry,
        private readonly DecisionTelemetry          $telemetry,
    ) {}

    // ── Public entry point ────────────────────────────────────────────────────

    /**
     * Make a deterministic memory storage decision.
     *
     * @param  string          $message Raw user message
     * @param  DecisionContext $context Endpoint, mode, dry_run, etc.
     * @param  bool            $trace   When true, evaluatedRules is populated
     *                                  (used by /debug/memory-decision only)
     */
    public function decide(
        string          $message,
        ?DecisionContext $context = null,
        bool            $trace   = false
    ): DecisionResult {
        $startUs       = (int) round(hrtime(true) / 1000);
        $context     ??= new DecisionContext();

        $ruleVersion   = $this->registry->getRuleVersion();
        $engineVersion = $this->registry->getEngineVersion();

        // ── 1. Context shortcuts: force / off ─────────────────────────────────
        if ($context->isForced()) {
            $result = $this->result(
                remember:      true,
                type:          'fact',
                confidence:    1.0,
                matchedRules:  [],
                weights:       [],
                reason:        'Forced by memory_mode=force.',
                reasonCode:    DecisionReasonCode::FORCED_MODE,
                via:           'forced',
                ruleVersion:   $ruleVersion,
                engineVersion: $engineVersion,
                volatility:    'persistent',
                elapsedUs:     $this->elapsed($startUs),
            );
            $this->telemetry->record(true, DecisionReasonCode::FORCED_MODE, [], $engineVersion, $ruleVersion);
            return $result;
        }

        if ($context->isDisabled()) {
            $result = $this->result(
                remember:      false,
                type:          null,
                confidence:    1.0,
                matchedRules:  [],
                weights:       [],
                reason:        'Memory disabled by memory_mode=off.',
                reasonCode:    DecisionReasonCode::DISABLED_MODE,
                via:           'disabled',
                ruleVersion:   $ruleVersion,
                engineVersion: $engineVersion,
                volatility:    'volatile',
                elapsedUs:     $this->elapsed($startUs),
            );
            $this->telemetry->record(false, DecisionReasonCode::DISABLED_MODE, [], $engineVersion, $ruleVersion);
            return $result;
        }

        // ── 2. Normalize ──────────────────────────────────────────────────────
        $normalized = $this->normalizer->normalize($message);

        // ── 3. Code detection ─────────────────────────────────────────────────
        if ($this->codeDetection->isCodeHeavy($message)) {
            $result = $this->result(
                remember:      false,
                type:          null,
                confidence:    0.0,
                matchedRules:  [],
                weights:       [],
                reason:        'Code-heavy message detected; not stored.',
                reasonCode:    DecisionReasonCode::CODE_DETECTED,
                via:           'code_skip',
                ruleVersion:   $ruleVersion,
                engineVersion: $engineVersion,
                volatility:    'volatile',
                elapsedUs:     $this->elapsed($startUs),
            );
            $this->telemetry->record(false, DecisionReasonCode::CODE_DETECTED, [], $engineVersion, $ruleVersion);
            return $result;
        }

        // ── 4. Rule evaluation ────────────────────────────────────────────────
        $rules         = $this->registry->getRules();
        $thresholdW    = (int)   config('memory_rules.confidence_threshold_weight', 100);
        $storeThreshold = (float) config('memory_rules.confidence_store_threshold', 0.55);
        $nearMissLow   = (float) config('memory_rules.near_miss_low_threshold', 0.40);

        $matchedRules   = [];
        $matchedWeights = [];
        $matchedType    = null;
        $matchedVia     = 'rule_engine';
        $matchedReason  = '';
        $matchedCode    = DecisionReasonCode::NO_RULES_MATCHED;
        $matchedVolat   = 'persistent';
        $evaluatedRules = [];
        $totalWeight    = 0;
        $terminalHit    = false;
        $terminalRule   = null;

        foreach ($rules as $rule) {
            $didMatch = $rule->matches($normalized);

            if ($trace) {
                $evaluatedRules[] = [
                    'id'       => $rule->id,
                    'matched'  => $didMatch,
                    'weight'   => $rule->weight,
                    'terminal' => $rule->terminal,
                    'group'    => $rule->group,
                ];
            }

            if (! $didMatch) {
                continue;
            }

            // Accumulate matched rule info
            $matchedRules[]   = $rule->id;
            $matchedWeights[] = $rule->weight;
            $totalWeight     += $rule->weight;

            // First match sets the primary type/code/volatility
            if ($matchedType === null) {
                $matchedType   = $rule->type;
                $matchedCode   = $rule->reasonCode;
                $matchedVolat  = $rule->volatility;
            }

            // Terminal match — stop immediately
            if ($rule->terminal) {
                $terminalHit  = true;
                $terminalRule = $rule;
                break;
            }
        }

        // ── 5. Confidence calculation (threshold-anchored) ────────────────────
        if ($terminalHit && $terminalRule !== null) {
            // Terminal skip/negative rules have weight=0 → confidence=0 → never stored
            // Terminal imperative/identity rules have weight=110+ → confidence ≥ 1.0 → always stored
            $confidence = $terminalRule->weight > 0
                ? min($terminalRule->weight / $thresholdW, 1.0)
                : 0.0;
        } else {
            $confidence = $totalWeight > 0
                ? min($totalWeight / $thresholdW, 1.0)
                : 0.0;
        }

        // ── 6. Determine remember flag ────────────────────────────────────────
        if (empty($matchedRules)) {
            $remember     = false;
            $matchedCode  = DecisionReasonCode::NO_RULES_MATCHED;
            $matchedType  = null;
            $matchedReason = 'No stable long-term personal information detected.';
            $matchedVolat = 'volatile';

        } elseif ($terminalHit && $terminalRule !== null && $terminalRule->weight === 0) {
            // Terminal skip / negative rule
            $remember      = false;
            $matchedReason = $this->buildReason(false, $matchedRules, $terminalRule->description);
            $matchedVia    = ($terminalRule->group === 'negative') ? 'negative_rule' : 'skip_pattern';

        } elseif ($confidence >= $storeThreshold) {
            $remember      = true;
            $matchedReason = $this->buildReason(true, $matchedRules, null);

        } else {
            $remember      = false;
            $matchedCode   = DecisionReasonCode::CONFIDENCE_BELOW_THRESHOLD;
            $matchedReason = sprintf(
                'Confidence %.2f below threshold %.2f; not storing.',
                $confidence,
                $storeThreshold
            );
        }

        // ── 7. Volatility finalisation ────────────────────────────────────────
        // Messages with temporal indicators are volatile regardless of rule match.
        $volatility = $this->classifyVolatility($normalized, $matchedVolat);

        // ── 8. Near-miss logging ──────────────────────────────────────────────
        $isNearMiss = false;
        if (! $remember && $confidence >= $nearMissLow && $confidence < $storeThreshold) {
            $isNearMiss = true;
            Log::info('memory.decision.near_miss', [
                'confidence'    => $confidence,
                'threshold'     => $storeThreshold,
                'matched_rules' => $matchedRules,
                'message_hash'  => hash('sha256', $normalized), // NO raw content in logs
                'engine_v'      => $engineVersion,
                'rule_v'        => $ruleVersion,
            ]);
        }

        // ── 9. Structured decision log ────────────────────────────────────────
        Log::info('memory.decision', [
            'remember'       => $remember,
            'type'           => $matchedType,
            'confidence'     => $confidence,
            'reason_code'    => $matchedCode,
            'matched_rules'  => $matchedRules,
            'via'            => $matchedVia,
            'engine_v'       => $engineVersion,
            'rule_v'         => $ruleVersion,
            'near_miss'      => $isNearMiss,
            'message_hash'   => hash('sha256', $normalized),
        ]);

        // ── 10. Telemetry ─────────────────────────────────────────────────────
        $this->telemetry->record(
            $remember,
            $matchedCode,
            $matchedRules,
            $engineVersion,
            $ruleVersion,
            $isNearMiss
        );

        return $this->result(
            remember:       $remember,
            type:           $matchedType,
            confidence:     round($confidence, 4),
            matchedRules:   $matchedRules,
            weights:        $matchedWeights,
            reason:         $matchedReason,
            reasonCode:     $matchedCode,
            via:            $matchedVia,
            ruleVersion:    $ruleVersion,
            engineVersion:  $engineVersion,
            volatility:     $volatility,
            evaluatedRules: $evaluatedRules,
            elapsedUs:      $this->elapsed($startUs),
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Classify volatility based on temporal indicators in the message.
     * Rule-matched volatility is overridden when temporal signals are present.
     */
    private function classifyVolatility(string $normalizedMessage, string $ruleVolatility): string
    {
        if ($ruleVolatility === 'volatile') {
            return 'volatile';
        }

        // Temporal signals that indicate a transient statement
        $temporalPatterns = [
            // 1. Standard & Formal Temporal (Adult, Writer, Sophisticated)
            '/\b(today|tonight|currently|presently|nowadays|momentarily|at present|this (week|month|year|quarter|semester))\b/i',
            '/\b(temporarily|for the time being|provisionally|transiently|until further notice|for now|for the moment)\b/i',
            '/\b(as of (now|today|this moment)|at this juncture|in the current climate)\b/i',

            // 2. Ultra-Casual & Shortened (Chill, Coder, Regular Typing, Text-Speak)
            '/\b(rn|right now|atm|curr|jus for now|4 now|tday|2nite|tonite)\b/i',
            '/\b(asap|this sec|this minute|right away|off the cuff)\b/i',
            
            // 3. Conversational & Storytelling (Regular, Child, Relatable)
            '/\b(lately|cently|recently|just a bit ago|the past few days|these days|anymore)\b/i',
            '/\b(all of a sudden|suddenly|just then|right then and there)\b/i',

            // 4. Impermanence & Transition Indicators (Sophisticated, Writer, Coder)
            '/\b(fleeting|passing phase|temporary fix|stopgap|workaround|interim|makeshift|adhoc|ad-hoc)\b/i',
            '/\b(subject to change|tentative|placeholder| WIP |tbd|tbc)\b/i',

            // 5. Emotional/Venting Impermanence (Chill, Unprofessional, Child, Regular)
            '/\b(just chilling|just trying|currently crying|rn honestly|honestly right now|at this point)\b/i',
            '/\b(for a hot second|for a minute|just getting started|in a phase)\b/i'
        ];

        foreach ($temporalPatterns as $pattern) {
            if (@preg_match($pattern, $normalizedMessage) === 1) {
                return 'volatile';
            }
        }

        return 'persistent';
    }

    /**
     * Build a human-readable reason string.
     * These CAN change between engine versions — use reason_code for machine consumption.
     */
    private function buildReason(bool $remember, array $matchedRules, ?string $override): string
    {
        if ($override !== null) {
            return $override . '; not stored.';
        }

        if (! $remember) {
            return 'No stable long-term personal information detected.';
        }

        $count = count($matchedRules);
        $top   = $matchedRules[0] ?? 'unknown';

        if ($count === 1) {
            return "Matched rule [{$top}] with high confidence.";
        }

        return "Matched {$count} rules [{$top}, ...] with combined confidence.";
    }

    /**
     * Construct a DecisionResult. Named parameters make callsites self-documenting.
     */
    private function result(
        bool    $remember,
        ?string $type,
        float   $confidence,
        array   $matchedRules,
        array   $weights,
        string  $reason,
        string  $reasonCode,
        string  $via,
        int     $ruleVersion,
        int     $engineVersion,
        string  $volatility,
        array   $evaluatedRules = [],
        int     $elapsedUs      = 0,
    ): DecisionResult {
        return new DecisionResult(
            remember:       $remember,
            type:           $type,
            confidence:     $confidence,
            matchedRules:   $matchedRules,
            weights:        $weights,
            reason:         $reason,
            reasonCode:     $reasonCode,
            via:            $via,
            ruleVersion:    $ruleVersion,
            engineVersion:  $engineVersion,
            volatility:     $volatility,
            evaluatedRules: $evaluatedRules,
            elapsedUs:      $elapsedUs,
        );
    }

    private function elapsed(int $startUs): int
    {
        return (int) round(hrtime(true) / 1000) - $startUs;
    }
}
