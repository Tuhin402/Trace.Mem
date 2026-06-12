<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Context Assembly — Temporal Scoring Weights
    |--------------------------------------------------------------------------
    |
    | These weights control how the context assembly service ranks memories
    | when the user's query has temporal / schedule intent.
    |
    | Two profiles exist:
    |
    |   'default'  — Used for non-schedule queries. All multipliers are 1.0
    |                 and all schedule-specific boosts are 0.0, preserving
    |                 the original scoring behaviour exactly.
    |
    |   'schedule' — Used for schedule-like queries. Values here are
    |                 *maximum* effects; actual effect is scaled by the
    |                 query's intent_strength (0.0–1.0).
    |
    | To tune: adjust the 'schedule' values below. The default profile
    | should generally stay at identity values unless you want to
    | globally shift non-schedule ranking behaviour.
    |
    */

    'default' => [
        'temporal_boost_cap'     => 0.35,
        'temporal_penalty_base'  => 0.05,
        'importance_weight'      => 1.0,
        'recency_weight'         => 1.0,
        'schedule_intent_boost'  => 0.0,
        'recurring_boost'        => 0.0,
        'exact_date_match_boost' => 0.0,
        'non_temporal_penalty'   => 0.0,
        'future_relevance_boost' => 0.0,
    ],

    'schedule' => [
        // ── Caps & base values (added to defaults, scaled by strength) ──
        'temporal_boost_cap_add'     => 0.25,   // added on top of default 0.35
        'temporal_penalty_base_add'  => 0.15,   // added on top of default 0.05

        // ── Multiplier dampening (subtracted from 1.0, scaled by strength) ──
        'importance_weight_reduction' => 0.50,  // at full strength: 1.0 - 0.50 = 0.50
        'importance_weight_floor'     => 0.35,  // never goes below this
        'recency_weight_reduction'    => 0.30,  // at full strength: 1.0 - 0.30 = 0.70
        'recency_weight_floor'        => 0.50,  // never goes below this

        // ── Additive boosts (scaled by strength, conditionally applied) ──
        'schedule_intent_boost'       => 0.20,
        'recurring_boost'             => 0.15,
        'exact_date_match_boost'      => 0.25,
        'non_temporal_penalty'        => 0.15,
        'future_relevance_boost'      => 0.10,
    ],

];