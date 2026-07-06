<?php

namespace App\Services\Memory\Decision;

/**
 * Machine-stable reason codes for every decision outcome.
 *
 * These constants NEVER change — they are written into logs, Redis counters,
 * and memory metadata. Human-readable reasons in DecisionResult may evolve;
 * reason codes must not.
 *
 * Naming convention: CATEGORY_DESCRIPTION (uppercase, underscore-separated).
 */
final class DecisionReasonCode
{
    // ── Force / disable modes ─────────────────────────────────────────────────
    public const FORCED_MODE    = 'FORCED_MODE';
    public const DISABLED_MODE  = 'DISABLED_MODE';

    // ── Early-exit detection ──────────────────────────────────────────────────
    public const CODE_DETECTED  = 'CODE_DETECTED';

    // ── Negative rules ────────────────────────────────────────────────────────
    public const NEGATIVE_RULE_MATCH = 'NEGATIVE_RULE_MATCH';

    // ── Skip patterns ─────────────────────────────────────────────────────────
    public const SKIP_GREETING  = 'SKIP_GREETING';
    public const SKIP_QUESTION  = 'SKIP_QUESTION';
    public const SKIP_TASK      = 'SKIP_TASK';
    public const SKIP_MATH      = 'SKIP_MATH';
    public const SKIP_JOKE      = 'SKIP_JOKE';
    public const SKIP_SHORT     = 'SKIP_SHORT';

    // ── Imperative instructions ───────────────────────────────────────────────
    public const IMPERATIVE_REMEMBER = 'IMPERATIVE_REMEMBER';

    // ── Identity ──────────────────────────────────────────────────────────────
    public const IDENTITY_NAME_MATCH     = 'IDENTITY_NAME_MATCH';
    public const IDENTITY_PRONOUN_MATCH  = 'IDENTITY_PRONOUN_MATCH';
    public const IDENTITY_BIRTHDAY_MATCH = 'IDENTITY_BIRTHDAY_MATCH';
    public const IDENTITY_CONTACT_MATCH  = 'IDENTITY_CONTACT_MATCH';

    // ── Facts ─────────────────────────────────────────────────────────────────
    public const FACT_LOCATION_MATCH = 'FACT_LOCATION_MATCH';
    public const FACT_JOB_MATCH      = 'FACT_JOB_MATCH';
    public const FACT_DIETARY_MATCH  = 'FACT_DIETARY_MATCH';

    // ── Preferences ───────────────────────────────────────────────────────────
    public const PREFERENCE_MATCH          = 'PREFERENCE_MATCH';
    public const PREFERENCE_NEGATIVE_MATCH = 'PREFERENCE_NEGATIVE_MATCH';

    // ── Constraints / rules ───────────────────────────────────────────────────
    public const CONSTRAINT_MATCH = 'CONSTRAINT_MATCH';

    // ── Skills ────────────────────────────────────────────────────────────────
    public const SKILL_MATCH = 'SKILL_MATCH';

    // ── Habits / goals ────────────────────────────────────────────────────────
    public const HABIT_MATCH = 'HABIT_MATCH';
    public const GOAL_MATCH  = 'GOAL_MATCH';

    // ── Tool preferences ──────────────────────────────────────────────────────
    public const TOOL_FRAMEWORK_MATCH = 'TOOL_FRAMEWORK_MATCH';
    public const TOOL_EDITOR_MATCH    = 'TOOL_EDITOR_MATCH';
    public const TOOL_DB_MATCH        = 'TOOL_DB_MATCH';
    public const TOOL_LANG_MATCH      = 'TOOL_LANG_MATCH';
    public const TOOL_OS_MATCH        = 'TOOL_OS_MATCH';
    public const TOOL_PKG_MATCH       = 'TOOL_PKG_MATCH';

    // ── Communication ────────────────────────────────────────────────────────
    public const COMMUNICATION_MATCH = 'COMMUNICATION_MATCH';

    // ── Decision outcomes ────────────────────────────────────────────────────
    public const CONFIDENCE_BELOW_THRESHOLD = 'CONFIDENCE_BELOW_THRESHOLD';
    public const NO_RULES_MATCHED           = 'NO_RULES_MATCHED';

    // Not instantiable.
    private function __construct() {}
}
