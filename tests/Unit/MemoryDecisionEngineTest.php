<?php

use App\Services\Memory\CodeDetectionService;
use App\Services\Memory\Decision\DecisionContext;
use App\Services\Memory\Decision\DecisionReasonCode;
use App\Services\Memory\Decision\DecisionTelemetry;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\Memory\Decision\MemoryRuleRegistry;
use App\Services\Memory\MemoryNormalizationService;

// ── Helper: build engine with real services (no mocks — this tests the full stack) ─

function makeEngine(): MemoryDecisionEngine
{
    return new MemoryDecisionEngine(
        normalizer:     new MemoryNormalizationService(),
        codeDetection:  new CodeDetectionService(),
        registry:       new MemoryRuleRegistry(),
        telemetry:      new class extends DecisionTelemetry {
            // Override to prevent Redis calls in unit tests
            public function record(bool $remember, string $reasonCode, array $matchedRuleIds, int $engineVersion, int $ruleVersion, bool $nearMiss = false): void {}
        },
    );
}

function autoContext(): DecisionContext
{
    return new DecisionContext(memoryMode: 'auto');
}

// ════════════════════════════════════════════════════════════════════
// GROUP: Force / disable modes
// ════════════════════════════════════════════════════════════════════

test('memory_mode=force always returns remember=true regardless of message', function () {
    $engine = makeEngine();
    $ctx    = new DecisionContext(memoryMode: 'force');

    $result = $engine->decide('What is the weather today?', $ctx);

    expect($result->remember)->toBeTrue()
        ->and($result->via)->toBe('forced')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::FORCED_MODE)
        ->and($result->confidence)->toBe(1.0);
});

test('memory_mode=force works even for greetings', function () {
    $engine = makeEngine();
    $ctx    = new DecisionContext(memoryMode: 'force');

    $result = $engine->decide('Hi there!', $ctx);

    expect($result->remember)->toBeTrue()
        ->and($result->via)->toBe('forced');
});

test('memory_mode=off always returns remember=false', function () {
    $engine = makeEngine();
    $ctx    = new DecisionContext(memoryMode: 'off');

    $result = $engine->decide('My name is Alex', $ctx);

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('disabled')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::DISABLED_MODE);
});

test('memory_mode=off blocks even imperative instructions', function () {
    $engine = makeEngine();
    $ctx    = new DecisionContext(memoryMode: 'off');

    $result = $engine->decide('Remember that I prefer TypeScript', $ctx);

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('disabled');
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Code detection
// ════════════════════════════════════════════════════════════════════

test('code-heavy message is skipped (fenced block)', function () {
    $engine = makeEngine();

    $result = $engine->decide("My name is Alex\n```php\n\$user = User::find(1);\necho \$user->name;\n```", autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('code_skip')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::CODE_DETECTED);
});

test('code-heavy message is skipped (CLI commands)', function () {
    $engine = makeEngine();

    $result = $engine->decide("git push origin main\ndocker-compose up -d", autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('code_skip');
});

test('plain prose with code keywords is not falsely skipped', function () {
    $engine = makeEngine();

    $result = $engine->decide('I prefer using Laravel for backend projects', autoContext());

    // Should NOT be skipped as code — should remember
    expect($result->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Negative rules
// ════════════════════════════════════════════════════════════════════

test('pretend roleplay is blocked by negative rule', function () {
    $engine = makeEngine();

    $result = $engine->decide('Pretend my name is Alex', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('negative_rule')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('imagine hypothetical is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide('Imagine I live in Paris and work at Google', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('translate request is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide('Translate: My name is Alex', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('suppose hypothetical is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide("Suppose I worked at Microsoft", autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('let\'s say hypothetical is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide("Let's say my name is Bob", autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('explicit deny instruction is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide("Don't store this: I prefer Python", autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('just kidding framing is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide('Just kidding, I hate Laravel', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

test('example framing is blocked', function () {
    $engine = makeEngine();

    $result = $engine->decide('For example, my name would be Sarah', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::NEGATIVE_RULE_MATCH);
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Skip patterns
// ════════════════════════════════════════════════════════════════════

test('greeting is skipped', function () {
    $engine = makeEngine();

    $result = $engine->decide('Hello', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->via)->toBe('skip_pattern')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::SKIP_GREETING);
});

test('good morning is skipped', function () {
    $engine = makeEngine();

    expect($engine->decide('Good morning!', autoContext())->remember)->toBeFalse();
});

test('general knowledge question is skipped', function () {
    $engine = makeEngine();

    $result = $engine->decide('What is the capital of France?', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::SKIP_QUESTION);
});

test('how does X work question is skipped', function () {
    $engine = makeEngine();

    expect($engine->decide('How does garbage collection work?', autoContext())->remember)->toBeFalse();
});

test('code generation task is skipped', function () {
    $engine = makeEngine();

    $result = $engine->decide('Write a PHP function to sort an array', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::SKIP_TASK);
});

test('create task is skipped', function () {
    $engine = makeEngine();

    expect($engine->decide('Create a REST API endpoint', autoContext())->remember)->toBeFalse();
});

test('math expression is skipped', function () {
    $engine = makeEngine();

    $result = $engine->decide('2 + 2 = ?', autoContext());

    expect($result->remember)->toBeFalse()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::SKIP_MATH);
});

test('very short message is skipped', function () {
    $engine = makeEngine();

    expect($engine->decide('ok', autoContext())->remember)->toBeFalse();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Imperative instructions (force-store)
// ════════════════════════════════════════════════════════════════════

test('"Remember that" forces storage', function () {
    $engine = makeEngine();

    $result = $engine->decide('Remember that my favourite IDE is PHPStorm', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IMPERATIVE_REMEMBER)
        ->and($result->confidence)->toBe(1.0);
});

test('"Don\'t forget I" forces storage', function () {
    $engine = makeEngine();

    $result = $engine->decide("Don't forget I prefer PostgreSQL", autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IMPERATIVE_REMEMBER);
});

test('"Always remember" forces storage', function () {
    $engine = makeEngine();

    expect($engine->decide('Always remember I use Laravel', autoContext())->remember)->toBeTrue();
});

test('"Keep in mind that" forces storage', function () {
    $engine = makeEngine();

    expect($engine->decide('Keep in mind that I work on Linux', autoContext())->remember)->toBeTrue();
});

test('"Note that" forces storage', function () {
    $engine = makeEngine();

    expect($engine->decide('Note that I am vegetarian', autoContext())->remember)->toBeTrue();
});

test('"Make a note that" forces storage', function () {
    $engine = makeEngine();

    expect($engine->decide('Make a note that I prefer TypeScript', autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Identity rules
// ════════════════════════════════════════════════════════════════════

test('"My name is X" stores as fact with full confidence', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->type)->toBe('fact')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IDENTITY_NAME_MATCH)
        ->and($result->confidence)->toBe(1.0)
        ->and($result->matchedRules)->toContain('identity.name');
});

test('"Call me X" triggers identity name rule', function () {
    $engine = makeEngine();

    $result = $engine->decide('Call me Sarah', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IDENTITY_NAME_MATCH);
});

test('pronoun declaration is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('My pronouns are they/them', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IDENTITY_PRONOUN_MATCH);
});

test('birthday declaration is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide("My birthday is March 15th", autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::IDENTITY_BIRTHDAY_MATCH);
});

test('age declaration is stored', function () {
    $engine = makeEngine();

    expect($engine->decide("I'm 28 years old", autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Preference rules
// ════════════════════════════════════════════════════════════════════

test('"I like X" stores as preference', function () {
    $engine = makeEngine();

    $result = $engine->decide('I like React', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->type)->toBe('preference')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::PREFERENCE_MATCH);
});

test('"I love X" stores as preference', function () {
    $engine = makeEngine();

    expect($engine->decide('I love TypeScript', autoContext())->remember)->toBeTrue();
});

test('"I prefer X" stores as preference', function () {
    $engine = makeEngine();

    expect($engine->decide('I prefer PostgreSQL over MySQL', autoContext())->remember)->toBeTrue();
});

test('"My favourite X" stores as preference', function () {
    $engine = makeEngine();

    $result = $engine->decide('My favourite editor is Neovim', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::PREFERENCE_MATCH);
});

test('"I hate X" stores as negative preference', function () {
    $engine = makeEngine();

    $result = $engine->decide('I hate PHP', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::PREFERENCE_NEGATIVE_MATCH);
});

test('"I avoid X" stores as negative preference', function () {
    $engine = makeEngine();

    expect($engine->decide('I avoid using Windows', autoContext())->remember)->toBeTrue();
});

test('"I never use X" stores as negative preference', function () {
    $engine = makeEngine();

    expect($engine->decide('I never use jQuery', autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Fact rules
// ════════════════════════════════════════════════════════════════════

test('location declaration is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I live in Dhaka', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::FACT_LOCATION_MATCH);
});

test('timezone declaration is stored', function () {
    $engine = makeEngine();

    expect($engine->decide('My timezone is IST', autoContext())->remember)->toBeTrue();
});

test('employer declaration is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I work at Google', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::FACT_JOB_MATCH);
});

test('profession declaration is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide("I'm a software engineer", autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::FACT_JOB_MATCH);
});

test('dietary restriction is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide("I'm vegetarian", autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::FACT_DIETARY_MATCH);
});

test('allergy declaration is stored', function () {
    $engine = makeEngine();

    expect($engine->decide("I'm allergic to peanuts", autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Skill rules
// ════════════════════════════════════════════════════════════════════

test('"I know how to X" stores as skill', function () {
    $engine = makeEngine();

    $result = $engine->decide('I know how to build APIs in Go', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->type)->toBe('skill')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::SKILL_MATCH);
});

test('"I can X" stores as skill', function () {
    $engine = makeEngine();

    expect($engine->decide('I can write Rust', autoContext())->remember)->toBeTrue();
});

test('"I built X" stores as skill', function () {
    $engine = makeEngine();

    expect($engine->decide("I've built microservices with Docker", autoContext())->remember)->toBeTrue();
});

test('"I maintain X" stores as skill', function () {
    $engine = makeEngine();

    expect($engine->decide('I maintain an open source library', autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Tool preferences
// ════════════════════════════════════════════════════════════════════

test('framework preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I use Laravel for backend development', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_FRAMEWORK_MATCH);
});

test('editor preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I use VSCode as my editor', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_EDITOR_MATCH);
});

test('database preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I use PostgreSQL for all my projects', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_DB_MATCH);
});

test('programming language preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I code primarily in PHP', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_LANG_MATCH);
});

test('package manager preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I use pnpm as my package manager', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_PKG_MATCH);
});

test('OS preference is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I use macOS for development', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::TOOL_OS_MATCH);
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Constraints and communication
// ════════════════════════════════════════════════════════════════════

test('"Always use X" constraint is stored as rule type', function () {
    $engine = makeEngine();

    $result = $engine->decide('Always use bullet points in your responses', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->type)->toBe('rule')
        ->and($result->reasonCode)->toBe(DecisionReasonCode::CONSTRAINT_MATCH);
});

test('"Never include X" constraint is stored', function () {
    $engine = makeEngine();

    expect($engine->decide('Never include code unless I ask', autoContext())->remember)->toBeTrue();
});

test('"Use tabs for indentation" formatting rule is stored', function () {
    $engine = makeEngine();

    expect($engine->decide('Use tabs for indentation', autoContext())->remember)->toBeTrue();
});

test('response style preference is stored', function () {
    $engine = makeEngine();

    expect($engine->decide('I prefer detailed answers', autoContext())->remember)->toBeTrue();
});

test('"Get to the point" communication preference is stored', function () {
    $engine = makeEngine();

    expect($engine->decide('Get to the point', autoContext())->remember)->toBeTrue();
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Confidence algorithm validation
// ════════════════════════════════════════════════════════════════════

test('identity.name produces confidence of 1.0 (weight 100, threshold 100)', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext());

    expect($result->confidence)->toBe(1.0);
});

test('imperative rule produces confidence >= 1.0 (weight 110, capped at 1.0)', function () {
    $engine = makeEngine();

    $result = $engine->decide('Remember that my name is Alex', autoContext());

    expect($result->confidence)->toBe(1.0);
});

test('skipped message has confidence of 0.0', function () {
    $engine = makeEngine();

    $result = $engine->decide('Hello', autoContext());

    expect($result->confidence)->toBe(0.0);
});

test('negative rule match has confidence of 0.0', function () {
    $engine = makeEngine();

    $result = $engine->decide('Pretend my name is Alex', autoContext());

    expect($result->confidence)->toBe(0.0);
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Determinism — same input always produces same output
// ════════════════════════════════════════════════════════════════════

test('same message produces identical results on repeated calls', function () {
    $engine = makeEngine();
    $msg    = 'I prefer PostgreSQL over MySQL and I live in Dhaka';
    $ctx    = autoContext();

    $r1 = $engine->decide($msg, $ctx);
    $r2 = $engine->decide($msg, $ctx);
    $r3 = $engine->decide($msg, $ctx);

    expect($r1->remember)->toBe($r2->remember)
        ->and($r2->remember)->toBe($r3->remember)
        ->and($r1->confidence)->toBe($r2->confidence)
        ->and($r2->confidence)->toBe($r3->confidence)
        ->and($r1->reasonCode)->toBe($r2->reasonCode)
        ->and($r1->matchedRules)->toBe($r2->matchedRules);
});

test('engine version and rule version are always present in result', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext());

    expect($result->engineVersion)->toBeInt()->toBeGreaterThan(0)
        ->and($result->ruleVersion)->toBeInt()->toBeGreaterThan(0);
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Volatility classification
// ════════════════════════════════════════════════════════════════════

test('persistent fact has volatility=persistent', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext());

    expect($result->volatility)->toBe('persistent');
});

test('temporal statement has volatility=volatile', function () {
    $engine = makeEngine();

    $result = $engine->decide('I am currently using Windows today', autoContext());

    // Even if it matches a tool rule, temporal keywords override to volatile
    expect($result->volatility)->toBe('volatile');
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Trace mode (for /debug/memory-decision)
// ════════════════════════════════════════════════════════════════════

test('trace=true populates evaluatedRules', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext(), trace: true);

    expect($result->evaluatedRules)->not->toBeEmpty()
        ->and($result->evaluatedRules[0])->toHaveKey('id')
        ->and($result->evaluatedRules[0])->toHaveKey('matched');
});

test('trace=false leaves evaluatedRules empty', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext(), trace: false);

    expect($result->evaluatedRules)->toBeEmpty();
});

test('toDebugArray includes all explainability fields', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext(), trace: true);
    $debug  = $result->toDebugArray();

    expect($debug)->toHaveKeys([
        'remember', 'type', 'confidence', 'matched_rules', 'reason',
        'reason_code', 'via', 'rule_version', 'engine_version',
        'volatility', 'weights', 'evaluated_rules', 'elapsed_us',
    ]);
});

// ════════════════════════════════════════════════════════════════════
// GROUP: toMemoryMetadata — version stamping
// ════════════════════════════════════════════════════════════════════

test('toMemoryMetadata includes engine_version and rule_version', function () {
    $engine = makeEngine();

    $result = $engine->decide('My name is Alex', autoContext());
    $meta   = $result->toMemoryMetadata();

    expect($meta)->toHaveKey('engine_version')
        ->and($meta)->toHaveKey('rule_version')
        ->and($meta)->toHaveKey('matched_rule')
        ->and($meta)->toHaveKey('reason_code');
});

// ════════════════════════════════════════════════════════════════════
// GROUP: Habit and goal rules
// ════════════════════════════════════════════════════════════════════

test('habit pattern is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('I usually start with code review in the morning', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::HABIT_MATCH);
});

test('goal pattern is stored', function () {
    $engine = makeEngine();

    $result = $engine->decide('My goal is to learn Rust this year', autoContext());

    expect($result->remember)->toBeTrue()
        ->and($result->reasonCode)->toBe(DecisionReasonCode::GOAL_MATCH);
});
