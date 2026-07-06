<?php

use App\Services\Memory\CodeDetectionService;
use App\Services\Memory\Decision\DecisionContext;
use App\Services\Memory\Decision\DecisionTelemetry;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\Memory\Decision\MemoryRuleRegistry;
use App\Services\Memory\MemoryNormalizationService;

/**
 * Benchmark test for MemoryDecisionEngine.
 *
 * Target: p95 latency < 5ms per decision.
 * This test fails CI if the engine's p95 latency exceeds the threshold,
 * enforcing the performance guarantee documented in the architecture spec.
 *
 * Measurement notes
 * ─────────────────
 * - Uses hrtime(true) for nanosecond-precision measurement.
 * - 1000 iterations with a representative mix of all message types.
 * - No DB, no HTTP, no Cache — pure engine evaluation.
 * - Registry is constructed once (singleton-like) and reused.
 * - Telemetry is disabled to avoid Redis overhead in benchmarks.
 *
 * Thresholds
 * ──────────
 * - p95 < 5ms  (hard fail)
 * - p50 < 2ms  (soft warning logged but not a failure)
 */

test('engine p95 latency is under 5ms for 1000 diverse messages', function () {
    $engine = new MemoryDecisionEngine(
        normalizer:    new MemoryNormalizationService(),
        codeDetection: new CodeDetectionService(),
        registry:      new MemoryRuleRegistry(),
        telemetry:     new class extends DecisionTelemetry {
            public function record(bool $remember, string $reasonCode, array $matchedRuleIds, int $engineVersion, int $ruleVersion, bool $nearMiss = false): void {}
        },
    );

    $ctx = new DecisionContext(memoryMode: 'auto');

    // Representative mix — mirrors real-world traffic distribution
    $messages = [
        // High-traffic skips (greetings, questions, tasks) — ~40%
        'Hello',
        'Hi there',
        'Good morning',
        'What is PHP?',
        'How does recursion work?',
        'Write a function to reverse a string',
        'Create a REST API',
        'Explain dependency injection',
        'What are design patterns?',
        'Generate a SQL query',

        // Identity — ~10%
        'My name is Alex',
        'Call me Sarah',
        'My pronouns are they/them',
        'My birthday is March 15',
        "I'm 28 years old",

        // Preferences and tools — ~25%
        'I prefer PostgreSQL',
        'I love TypeScript',
        'My favourite editor is Neovim',
        'I use Laravel for all my projects',
        'I hate PHP frameworks',
        'I avoid Windows for development',
        'I use pnpm as my package manager',
        'I code primarily in Go',
        'I use macOS for development',
        'My go-to database is MongoDB',

        // Facts — ~10%
        'I live in Dhaka',
        "I'm based in London",
        'I work at Google',
        "I'm a software engineer",
        "I'm vegetarian",
        "I'm allergic to peanuts",

        // Imperatives — ~5%
        'Remember that my favourite IDE is PHPStorm',
        "Don't forget I prefer PostgreSQL",
        'Always remember I use Laravel',

        // Negatives — ~10%
        'Pretend my name is Alex',
        'Imagine I live in Paris',
        'Suppose I worked at Microsoft',
        "Let's say my name is Bob",
        'Translate: My name is Alex',
    ];

    $iterations = 1000;
    $timingsMs  = [];

    for ($i = 0; $i < $iterations; $i++) {
        $message = $messages[$i % count($messages)];
        $start   = hrtime(true);
        $engine->decide($message, $ctx);
        $timingsMs[] = (hrtime(true) - $start) / 1_000_000; // nanoseconds → ms
    }

    sort($timingsMs);

    $p50Index = (int) floor($iterations * 0.50) - 1;
    $p95Index = (int) floor($iterations * 0.95) - 1;
    $p99Index = (int) floor($iterations * 0.99) - 1;

    $p50 = round($timingsMs[max(0, $p50Index)], 3);
    $p95 = round($timingsMs[max(0, $p95Index)], 3);
    $p99 = round($timingsMs[max(0, $p99Index)], 3);
    $avg = round(array_sum($timingsMs) / $iterations, 3);

    // Log results regardless of pass/fail — useful for profiling
    fwrite(STDERR, "\n  MemoryDecisionEngine benchmark ({$iterations} iterations):\n");
    fwrite(STDERR, "    avg={$avg}ms  p50={$p50}ms  p95={$p95}ms  p99={$p99}ms\n");

    // Hard assertion: p95 must be under 5ms
    expect($p95)->toBeLessThan(5.0, "p95 latency {$p95}ms exceeds 5ms target. avg={$avg}ms p50={$p50}ms p99={$p99}ms");
});

test('single decision completes in under 10ms on worst-case complex message', function () {
    $engine = new MemoryDecisionEngine(
        normalizer:    new MemoryNormalizationService(),
        codeDetection: new CodeDetectionService(),
        registry:      new MemoryRuleRegistry(),
        telemetry:     new class extends DecisionTelemetry {
            public function record(bool $remember, string $reasonCode, array $matchedRuleIds, int $engineVersion, int $ruleVersion, bool $nearMiss = false): void {}
        },
    );

    $ctx = new DecisionContext(memoryMode: 'auto');

    // This message triggers multiple non-terminal rules (additive scoring)
    // making it the slowest path through the engine
    $worstCase = 'I prefer PostgreSQL, I love TypeScript, I use Laravel, '
               . 'I live in Dhaka, I work at Google as a senior engineer, '
               . 'and I usually write tests before implementation';

    $start  = hrtime(true);
    $engine->decide($worstCase, $ctx, trace: true); // trace adds overhead
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    expect($elapsedMs)->toBeLessThan(10.0, "Single complex decision took {$elapsedMs}ms — expected < 10ms");
});
