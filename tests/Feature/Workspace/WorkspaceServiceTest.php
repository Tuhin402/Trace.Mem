<?php

use App\Enums\TeamRole;
use App\Models\ApiKey;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\ApiKeyService;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Mock ApiKeyService so no Redis / no entitlement checks hit in these tests ─
// autoCreateTestKey() calls ApiKeyService which calls SubscriptionCacheService
// (backed by Redis in production). The mock returns a fake key result so the
// workspace tests can verify workspace creation without needing a real Redis conn.

function mockApiKeyService(): void
{
    $mock = Mockery::mock(ApiKeyService::class);
    $mock->shouldReceive('createForUser')
        ->andReturnUsing(function (User $user, string $name, string $environment, array $options = [], $replacing = null, ?Team $workspace = null) {
            $key = ApiKey::forceCreate([
                'user_id'         => $user->id,
                'tenant_scope_id' => $user->tenant_scope_id,
                'workspace_id'    => $workspace?->id,
                'name'            => $name,
                'environment'     => $environment,
                'key_prefix'      => $environment === 'test' ? 'cmtest_' : 'cmlive_',
                'key_hash'        => hash('sha256', 'fake-plain-key-' . uniqid()),
                'key_last4'       => 'TEST',
                'mode'            => 'semantic_only',
                'rate_limit_max_requests'      => 60,
                'rate_limit_window_seconds'    => 60,
                'scopes'          => ['memory:read', 'memory:write'],
                'metadata'        => ['source' => 'test'],
            ]);

            return ['key' => $key, 'plain_key' => 'cmtest_fake'];
        });

    app()->instance(ApiKeyService::class, $mock);
}

// ── createDefaultWorkspace() ──────────────────────────────────────────────────

test('createDefaultWorkspace() creates a workspace with owner membership', function () {
    mockApiKeyService();

    $user = User::factory()->create(['account_type' => 'individual']);
    $service = app(WorkspaceService::class);

    $workspace = $service->createDefaultWorkspace($user);

    expect($workspace)->toBeInstanceOf(Team::class);
    expect($workspace->status)->toBe('active');
    expect($workspace->is_personal)->toBeTrue();

    $membership = $workspace->memberships()->where('user_id', $user->id)->first();
    expect($membership)->not()->toBeNull();
    expect($membership->role->value)->toBe(TeamRole::Owner->value);
});

test('createDefaultWorkspace() auto-creates exactly one test API key', function () {
    mockApiKeyService();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(WorkspaceService::class);

    $workspace = $service->createDefaultWorkspace($user);

    $keys = ApiKey::where('workspace_id', $workspace->id)->get();

    expect($keys)->toHaveCount(1);
    expect($keys->first()->environment)->toBe('test');
});

test('createDefaultWorkspace() does NOT create a live API key', function () {
    mockApiKeyService();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(WorkspaceService::class);

    $workspace = $service->createDefaultWorkspace($user);

    $liveKeys = ApiKey::where('workspace_id', $workspace->id)
        ->where('environment', 'live')
        ->count();

    expect($liveKeys)->toBe(0);
});

// ── createWorkspace() ─────────────────────────────────────────────────────────

test('createWorkspace() creates a named workspace with one test key', function () {
    mockApiKeyService();

    $user    = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace(
        user:    $user,
        name:    'Staging',
        purpose: 'For staging tests',
    );

    expect($workspace->name)->toBe('Staging');
    expect($workspace->purpose)->toBe('For staging tests');
    expect($workspace->is_personal)->toBeFalse();
    expect($workspace->status)->toBe('active');

    $keys = ApiKey::where('workspace_id', $workspace->id)->get();
    expect($keys)->toHaveCount(1);
    expect($keys->first()->environment)->toBe('test');
});

test('createWorkspace() records audit log on creation', function () {
    mockApiKeyService();

    $user    = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($user, 'Production');

    $this->assertDatabaseHas('workspace_audit_logs', [
        'workspace_id'  => $workspace->id,
        'actor_user_id' => $user->id,
        'action'        => 'workspace.created',
    ]);
});

// ── renameWorkspace() ─────────────────────────────────────────────────────────

test('renameWorkspace() renames a non-locked workspace', function () {
    mockApiKeyService();

    $user    = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($user, 'Old Name');
    $service->renameWorkspace($user, $workspace, 'New Name');

    expect($workspace->fresh()->name)->toBe('New Name');
});

test('renameWorkspace() records audit log on rename', function () {
    mockApiKeyService();

    $user    = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($user, 'Old Name');
    $service->renameWorkspace($user, $workspace, 'New Name');

    $this->assertDatabaseHas('workspace_audit_logs', [
        'workspace_id' => $workspace->id,
        'action'       => 'workspace.renamed',
    ]);
});

// ── archiveWorkspace() ────────────────────────────────────────────────────────

test('archiveWorkspace() archives a non-locked workspace', function () {
    mockApiKeyService();

    $user    = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($user, 'Temporary');
    $service->archiveWorkspace($user, $workspace);

    expect($workspace->fresh()->status)->toBe('archived');
});

test('archiveWorkspace() aborts with 422 for locked (default) workspace', function () {
    mockApiKeyService();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(WorkspaceService::class);

    $workspace = $service->createDefaultWorkspace($user);

    expect(fn () => $service->archiveWorkspace($user, $workspace))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ── removeMember() ────────────────────────────────────────────────────────────

test('removeMember() removes a member from a workspace', function () {
    mockApiKeyService();

    $owner   = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($owner, 'Team Workspace');

    $member = User::factory()->create();
    $workspace->memberships()->create([
        'user_id' => $member->id,
        'role'    => TeamRole::Member->value,
    ]);

    $service->removeMember($owner, $workspace, $member);

    $this->assertDatabaseMissing('team_members', [
        'team_id' => $workspace->id,
        'user_id' => $member->id,
    ]);
});

test('removeMember() aborts when trying to remove the only owner', function () {
    mockApiKeyService();

    $owner   = User::factory()->company()->create();
    $service = app(WorkspaceService::class);

    $workspace = $service->createWorkspace($owner, 'Solo Workspace');

    expect(fn () => $service->removeMember($owner, $workspace, $owner))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});
