<?php

use App\Models\ApiKey;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\ApiKeyService;
use App\Services\Auth\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\VerifyCsrfToken;

uses(RefreshDatabase::class);

// ── Mock entitlements so no Redis / no subscription DB queries hit ─────────────
// SubscriptionEntitlementService normally uses SubscriptionCacheService (Redis).
// In tests we return a permissive policy that allows test key creation.

function mockEntitlements(): void
{
    $mock = Mockery::mock(SubscriptionEntitlementService::class);
    $mock->shouldReceive('resolveForUser')->andReturn([
        'plan'                    => null,
        'subscription'            => null,
        'allow_test_keys'         => true,
        'allow_live_keys'         => false,
        'has_active_subscription' => false,
        'test_api_key_limit'      => 10,
        'live_api_key_limit'      => 0,
        'test_key_ttl_days'       => 30,
        'base_mode'               => 'semantic_only',
        'api_key_limit'           => 10,
    ]);

    app()->instance(SubscriptionEntitlementService::class, $mock);
}

// ── createForUser() binds the key to the workspace ────────────────────────────

test('createForUser() binds the key to the workspace', function () {
    mockEntitlements();

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;
    $service   = app(ApiKeyService::class);

    $result = $service->createForUser(
        user:        $user,
        name:        'test-key',
        environment: 'test',
        workspace:   $workspace,
    );

    expect($result['key'])->toBeInstanceOf(ApiKey::class);
    expect($result['key']->workspace_id)->toBe($workspace->id);
});

test('createForUser() without workspace still creates a key (workspace_id resolves to currentTeam)', function () {
    mockEntitlements();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(ApiKeyService::class);

    $result = $service->createForUser(
        user:        $user,
        name:        'no-ws-key',
        environment: 'test',
        workspace:   null,
    );

    // workspace=null → resolves to $user->currentTeam inside ApiKeyService
    expect($result['key'])->toBeInstanceOf(ApiKey::class);
    // workspace_id will equal user's default team
    expect($result['key']->workspace_id)->toBe($user->currentTeam->id);
});

// ── workspace_id immutability ─────────────────────────────────────────────────

test('workspace_id cannot be changed after creation via fill()', function () {
    mockEntitlements();

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;
    $service   = app(ApiKeyService::class);

    $result = $service->createForUser(
        user:        $user,
        name:        'immutable-key',
        environment: 'test',
        workspace:   $workspace,
    );

    $key = $result['key'];
    $originalWsId = $key->workspace_id;

    // Create a real team to avoid FK violations
    $other = Team::factory()->create();

    // Attempt to change workspace_id via fill
    $key->fill(['workspace_id' => $other->id]);
    $key->save();

    // DB value must be unchanged (workspace_id is guarded)
    expect(ApiKey::find($key->id)->workspace_id)->toBe($originalWsId);
});

// ── Dashboard: POST /api-keys creates key scoped to current workspace ─────────

test('POST /api-keys creates key scoped to current workspace', function () {
    mockEntitlements();

    $user = User::factory()->create(['account_type' => 'individual']);
    $ws   = $user->currentTeam;

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('api.keys.store'), [
            'name'        => 'new-test-key',
            'environment' => 'test',
        ]);

    $key = ApiKey::where('user_id', $user->id)
        ->where('environment', 'test')
        ->where('name', 'new-test-key')
        ->first();

    expect($key)->not()->toBeNull();
    expect($key->workspace_id)->toBe($ws->id);
});

test('guest cannot create an API key', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->post(route('api.keys.store'), [
            'name'        => 'hacked-key',
            'environment' => 'test',
        ])
        ->assertRedirect(route('login'));
});

// ── Middleware resolved_scope contains workspace_id ───────────────────────────

test('ApiKey model has workspace_id after createForUser()', function () {
    mockEntitlements();

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;
    $service   = app(ApiKeyService::class);

    $result = $service->createForUser(
        user:        $user,
        name:        'scope-test',
        environment: 'test',
        workspace:   $workspace,
    );

    $key = ApiKey::find($result['key']->id);

    // The key must be scoped to the workspace so the middleware can set workspace_id in resolved_scope
    expect($key->workspace_id)->toBe($workspace->id);
    expect($key->environment)->toBe('test');
});
