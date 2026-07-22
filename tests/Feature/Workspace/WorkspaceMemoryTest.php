<?php

use App\Models\Memory;
use App\Models\Team;
use App\Models\User;
use App\Services\Memory\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// ── store() with workspace_id ─────────────────────────────────────────────────

test('MemoryService::store() saves workspace_id when provided', function () {
    Queue::fake(); // Prevent ReinforceMemoriesJob from hitting Redis

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;
    $service   = app(MemoryService::class);

    $memory = $service->store(
        tenantId:    $user->tenant_scope_id,
        userId:      (string) $user->id,
        type:        'fact',
        content:     'Test memory content for workspace scoping',
        confidence:  0.7,
        workspaceId: $workspace->id,
    );

    expect($memory)->toBeInstanceOf(Memory::class);
    expect($memory->workspace_id)->toBe($workspace->id);
});

test('MemoryService::store() accepts null workspace_id (backward compat)', function () {
    Queue::fake();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(MemoryService::class);

    $memory = $service->store(
        tenantId:   $user->tenant_scope_id,
        userId:     (string) $user->id,
        type:       'fact',
        content:    'Legacy memory without workspace',
        confidence: 0.5,
    );

    expect($memory->workspace_id)->toBeNull();
});

// ── recall() workspace scoping ────────────────────────────────────────────────

test('MemoryService::recall() returns only memories for the given workspace', function () {
    Queue::fake();

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;

    // Create a second REAL workspace so FK constraint is satisfied
    $otherWorkspace = Team::factory()->create(['status' => 'active']);
    $otherWorkspace->memberships()->create([
        'user_id' => $user->id,
        'role'    => \App\Enums\TeamRole::Member->value,
    ]);

    $service = app(MemoryService::class);

    // Memory for workspace A
    $service->store(
        tenantId:    $user->tenant_scope_id,
        userId:      (string) $user->id,
        type:        'fact',
        content:     'Workspace A memory',
        confidence:  0.7,
        workspaceId: $workspace->id,
    );

    // Memory for workspace B
    $service->store(
        tenantId:    $user->tenant_scope_id,
        userId:      (string) $user->id,
        type:        'fact',
        content:     'Workspace B memory',
        confidence:  0.7,
        workspaceId: $otherWorkspace->id,
    );

    $results = $service->recall(
        tenantId:    $user->tenant_scope_id,
        userId:      (string) $user->id,
        workspaceId: $workspace->id,
    );

    expect($results)->toHaveCount(1);
    expect($results->first()->content)->toContain('Workspace A');
});

test('MemoryService::recall() without workspace_id returns all tenant+user memories', function () {
    Queue::fake();

    $user    = User::factory()->create(['account_type' => 'individual']);
    $service = app(MemoryService::class);

    $service->store($user->tenant_scope_id, (string) $user->id, 'fact', 'Memory 1', 0.7);
    $service->store($user->tenant_scope_id, (string) $user->id, 'fact', 'Memory 2', 0.7);

    $results = $service->recall(
        tenantId: $user->tenant_scope_id,
        userId:   (string) $user->id,
        limit:    10,
    );

    expect($results->count())->toBeGreaterThanOrEqual(2);
});

// ── workspace_id immutability ─────────────────────────────────────────────────

test('Memory workspace_id cannot be changed after creation', function () {
    Queue::fake();

    $user      = User::factory()->create(['account_type' => 'individual']);
    $workspace = $user->currentTeam;
    $service   = app(MemoryService::class);

    $memory = $service->store(
        tenantId:    $user->tenant_scope_id,
        userId:      (string) $user->id,
        type:        'fact',
        content:     'Immutable workspace memory',
        confidence:  0.7,
        workspaceId: $workspace->id,
    );

    $originalWsId = $memory->workspace_id;

    // Create a real team to avoid FK violations
    $other = Team::factory()->create();

    // Attempt to change workspace_id via fill
    $memory->fill(['workspace_id' => $other->id]);
    $memory->save();

    // DB value must be unchanged (workspace_id is guarded)
    expect(Memory::find($memory->id)->workspace_id)->toBe($originalWsId);
});
