<?php

namespace App\Services\Memory\Decision;

/**
 * Loads and indexes all enabled rules from config/memory_rules.php.
 *
 * Registered as a singleton in AppServiceProvider so config is parsed exactly
 * once per request (or per CLI invocation). Pure in-memory — no DB, no HTTP.
 *
 * Feature-flag groups
 * ───────────────────
 * Rules whose group is disabled via config('memory_rules.feature_flags') are
 * excluded from getRules(). Toggling env vars takes effect on next request.
 *
 * Locale support
 * ──────────────
 * Only locales listed in config('memory_rules.active_locales') are loaded.
 * Locale namespaces for future languages (bn, hi, ...) are zero-cost placeholders.
 *
 * Evaluation order
 * ────────────────
 * Rules are sorted by priority ASC so the engine evaluates in the correct order
 * without needing to know about priorities itself.
 */
class MemoryRuleRegistry
{
    /** @var RuleDefinition[] */
    private array $rules = [];

    private int   $ruleVersion;
    private int   $engineVersion;

    public function __construct()
    {
        $this->ruleVersion   = (int) config('memory_rules.rule_version', 1);
        $this->engineVersion = (int) config('memory_rules.engine_version', 1);
        $this->rules         = $this->loadRules();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Returns all enabled rules that belong to enabled feature-flag groups,
     * sorted by priority ascending (lowest priority number = evaluated first).
     *
     * @return RuleDefinition[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getRuleVersion(): int
    {
        return $this->ruleVersion;
    }

    public function getEngineVersion(): int
    {
        return $this->engineVersion;
    }

    // ── Private — loading ─────────────────────────────────────────────────────

    /**
     * @return RuleDefinition[]
     */
    private function loadRules(): array
    {
        $activeLocales = (array) config('memory_rules.active_locales', ['en']);
        $featureFlags  = (array) config('memory_rules.feature_flags', []);
        $localesConfig = (array) config('memory_rules.locales', []);

        $rules = [];

        foreach ($activeLocales as $locale) {
            $localeRules = $localesConfig[$locale] ?? [];

            foreach ($localeRules as $ruleData) {
                $rule = RuleDefinition::fromArray($ruleData, $locale);

                // Hard-disabled rules are never loaded
                if (! $rule->enabled) {
                    continue;
                }

                // Feature-flag group check
                $flagKey = 'enable_' . $rule->group . '_rules';
                if (isset($featureFlags[$flagKey]) && $featureFlags[$flagKey] === false) {
                    continue;
                }

                $rules[] = $rule;
            }
        }

        // Sort by priority ascending (lower number = higher priority = evaluated first)
        usort($rules, fn (RuleDefinition $a, RuleDefinition $b) => $a->priority <=> $b->priority);

        return $rules;
    }
}
