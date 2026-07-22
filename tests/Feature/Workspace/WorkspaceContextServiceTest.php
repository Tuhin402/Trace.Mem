<?php

use App\Enums\TeamRole;
use App\Exceptions\NoWorkspaceException;
use App\Models\Team;
use App\Models\User;
use App\Services\Workspace\WorkspaceContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeUserWithWorkspace(string $accountType = 'individual'): array
{
    $user = User::factory()->create(['account_type' => $accountType]);
    $workspace = $user->currentTeam; // created by UserFactory::configure()
    return [$user, $workspace];
}

// ── current() resolution ─────────────────────────────────────────────────────

test('current() returns the active workspace for a user', function () {
    [$user, $workspace] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    $resolved = $service->current($user);

    expect($resolved->id)->toBe($workspace->id);
});

test('current() falls back to personal workspace when current_team_id is stale', function () {
    [$user, $workspace] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    // Create a real team so the FK is satisfied, then delete it to make it "stale"
    $staleTeam = Team::factory()->create(['status' => 'active']);
    $staleId   = $staleTeam->id;
    $staleTeam->forceDelete(); // remove from DB — current_team_id now points to nothing

    // Bypass FK with raw update (SQLite allows this after the row is gone)
    \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
    $user->forceFill(['current_team_id' => $staleId])->save();
    \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');

    $resolved = $service->current($user->fresh());

    // Falls back to the user's real active workspace
    expect($resolved->id)->toBe($workspace->id);
});


test('current() throws NoWorkspaceException when user has no active workspaces', function () {
    $user = User::factory()->create(['account_type' => 'individual']);
    // Delete the auto-created team membership
    $user->teams()->detach();
    $user->forceFill(['current_team_id' => null])->save();
    $service = app(WorkspaceContextService::class);

    expect(fn () => $service->current($user->fresh()))
        ->toThrow(NoWorkspaceException::class);
});

test('current() skips archived workspaces and falls back to active one', function () {
    $user = User::factory()->create(['account_type' => 'individual']);

    // Archive the auto-created workspace
    $defaultWorkspace = $user->currentTeam;
    $defaultWorkspace->forceFill(['status' => 'archived'])->save();

    // Create a second active workspace
    $active = Team::factory()->create(['status' => 'active']);
    $active->memberships()->create([
        'user_id' => $user->id,
        'role'    => TeamRole::Owner->value,
    ]);

    $service = app(WorkspaceContextService::class);
    $resolved = $service->current($user->fresh());

    expect($resolved->id)->toBe($active->id);
});

// ── switchTo() ────────────────────────────────────────────────────────────────

test('switchTo() switches user to a workspace they belong to', function () {
    [$user] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    $second = Team::factory()->create(['status' => 'active']);
    $second->memberships()->create([
        'user_id' => $user->id,
        'role'    => TeamRole::Member->value,
    ]);

    $result = $service->switchTo($user, $second);

    expect($result)->toBeTrue();
    expect($user->fresh()->current_team_id)->toBe($second->id);
});

test('switchTo() returns false when user is not a member', function () {
    [$user] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    $other = Team::factory()->create(['status' => 'active']);

    $result = $service->switchTo($user, $other);

    expect($result)->toBeFalse();
    expect($user->fresh()->current_team_id)->not()->toBe($other->id);
});

test('switchTo() returns false for archived workspace', function () {
    [$user] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    $archived = Team::factory()->archived()->create();
    $archived->memberships()->create([
        'user_id' => $user->id,
        'role'    => TeamRole::Member->value,
    ]);

    $result = $service->switchTo($user, $archived);

    expect($result)->toBeFalse();
});

// ── isIndividual() ────────────────────────────────────────────────────────────

test('isIndividual() returns true for individual accounts', function () {
    [$user] = makeUserWithWorkspace('individual');
    $service = app(WorkspaceContextService::class);

    expect($service->isIndividual($user))->toBeTrue();
});

test('isIndividual() returns false for company accounts', function () {
    [$user] = makeUserWithWorkspace('tenant');
    $service = app(WorkspaceContextService::class);

    expect($service->isIndividual($user))->toBeFalse();
});

// ── isOwner() ─────────────────────────────────────────────────────────────────

test('isOwner() returns true when user is owner of current workspace', function () {
    [$user] = makeUserWithWorkspace();
    $service = app(WorkspaceContextService::class);

    expect($service->isOwner($user))->toBeTrue();
});

test('isOwner() returns false when user is only a member', function () {
    [$owner, $workspace] = makeUserWithWorkspace();

    $member = User::factory()->create();
    $workspace->memberships()->create([
        'user_id' => $member->id,
        'role'    => TeamRole::Member->value,
    ]);
    $member->forceFill(['current_team_id' => $workspace->id])->save();

    $service = app(WorkspaceContextService::class);

    expect($service->isOwner($member))->toBeFalse();
});
