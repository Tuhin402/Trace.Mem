<?php

use App\Services\Memory\CodeDetectionService;
use App\Services\Memory\Decision\DecisionContext;
use App\Services\Memory\Decision\DecisionTelemetry;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\Memory\Decision\MemoryRuleRegistry;
use App\Services\Memory\MemoryNormalizationService;

/**
 * Regression test suite for MemoryDecisionEngine.
 *
 * Reads tests/fixtures/decision_engine_messages.json and asserts that
 * every fixture produces the expected `remember` and `reason_code`.
 *
 * Purpose
 * ───────
 * Protects against accidental rule regressions. If a rule change
 * unintentionally flips an existing fixture from true→false or changes
 * its reason_code, this test fails immediately.
 *
 * To intentionally change behaviour for a fixture:
 *   1. Update the fixture entry with the new expected values.
 *   2. Bump `rule_version` in config/memory_rules.php.
 *   3. Commit both changes together.
 *
 * Adding new fixtures:
 *   Add entries to tests/fixtures/decision_engine_messages.json.
 *   No test code changes required.
 */

function makeRegressionEngine(): MemoryDecisionEngine
{
    return new MemoryDecisionEngine(
        normalizer:    new MemoryNormalizationService(),
        codeDetection: new CodeDetectionService(),
        registry:      new MemoryRuleRegistry(),
        telemetry:     new class extends DecisionTelemetry {
            public function record(bool $remember, string $reasonCode, array $matchedRuleIds, int $engineVersion, int $ruleVersion, bool $nearMiss = false): void {}
        },
    );
}

$fixtureFile = __DIR__ . '/../fixtures/decision_engine_messages.json';
$fixtures    = json_decode(file_get_contents($fixtureFile), true);

$engine = makeRegressionEngine();
$ctx    = new DecisionContext(memoryMode: 'auto');

foreach ($fixtures as $fixture) {
    $message          = $fixture['message'];
    $expectedRemember = $fixture['expected_remember'];
    $expectedCode     = $fixture['expected_reason_code'];

    test("regression: [{$expectedCode}] \"{$message}\"", function () use ($engine, $ctx, $message, $expectedRemember, $expectedCode) {
        $result = $engine->decide($message, $ctx);

        expect($result->remember)
            ->toBe($expectedRemember, "Expected remember={$expectedRemember} but got " . ($result->remember ? 'true' : 'false') . " for: \"{$message}\"\nGot reason_code: {$result->reasonCode}\nGot matched_rules: " . implode(', ', $result->matchedRules))
            ->and($result->reasonCode)
            ->toBe($expectedCode, "Expected reason_code={$expectedCode} but got {$result->reasonCode} for: \"{$message}\"");
    });
}
