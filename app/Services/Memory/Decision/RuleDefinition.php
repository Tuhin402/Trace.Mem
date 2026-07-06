<?php

namespace App\Services\Memory\Decision;

/**
 * Immutable value object representing a single memory detection rule.
 *
 * Fields
 * ──────
 *  id          — Unique dotted identifier. e.g. "identity.name"
 *                Never changes once assigned — stored in memory metadata.
 *  type        — Memory type produced when this rule matches: fact | preference | rule | skill
 *  group       — Feature-flag group: negative | skip | imperative | identity | ...
 *  priority    — Evaluation order. Lower number = evaluated first. Terminal rules
 *                at low priorities exit early and save CPU.
 *  weight      — Contribution to confidence score (0–110+).
 *                Skip/negative rules carry weight=0 (they prevent storage, not score it).
 *  terminal    — If true, the FIRST match halts all further rule evaluation.
 *                Use for high-confidence single-signal rules (name, pronouns, imperatives).
 *  enabled     — Hard disable. Use feature_flags in config for runtime toggling.
 *  volatility  — 'persistent' | 'volatile'. Stored in memory metadata for future decay.
 *  reason_code — From DecisionReasonCode constants. Never changes.
 *  description — Human-readable label for logging and debug output.
 *  patterns    — Array of PCRE regex strings. ANY match causes this rule to fire.
 *  locale      — Language namespace. Default 'en'. Future: 'bn', 'hi', etc.
 */
final class RuleDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $group,
        public readonly int    $priority,
        public readonly int    $weight,
        public readonly bool   $terminal,
        public readonly bool   $enabled,
        public readonly string $volatility,
        public readonly string $reasonCode,
        public readonly string $description,
        /** @var string[] */
        public readonly array  $patterns,
        public readonly string $locale = 'en',
    ) {}

    /**
     * Construct from a raw config array (as loaded from memory_rules.php).
     */
    public static function fromArray(array $data, string $locale = 'en'): self
    {
        return new self(
            id:          $data['id'],
            type:        $data['type'],
            group:       $data['group'],
            priority:    (int)  $data['priority'],
            weight:      (int)  $data['weight'],
            terminal:    (bool) $data['terminal'],
            enabled:     (bool) ($data['enabled'] ?? true),
            volatility:  $data['volatility'] ?? 'persistent',
            reasonCode:  $data['reason_code'],
            description: $data['description'],
            patterns:    (array) $data['patterns'],
            locale:      $locale,
        );
    }

    /**
     * Test this rule against a normalised message.
     * Returns true if ANY pattern matches.
     */
    public function matches(string $normalizedMessage): bool
    {
        foreach ($this->patterns as $pattern) {
            if (@preg_match($pattern, $normalizedMessage) === 1) {
                return true;
            }
        }
        return false;
    }
}
