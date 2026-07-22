<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\VerifyCsrfToken;

uses(RefreshDatabase::class);

// ── Helper: bypass CSRF for POST/PATCH tests ──────────────────────────────────
// Guest POST requests get 419 (no session = no CSRF token) before auth fires.
// withoutMiddleware(VerifyCsrfToken::class) lets auth middleware run correctly.

// ── Auth guard ────────────────────────────────────────────────────────────────

test('guests cannot access workspaces index', function () {
    $this->get(route('workspaces.index'))->assertRedirect(route('login'));
});

test('guests cannot create a workspace', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->post(route('workspaces.store'), ['name' => 'Test'])
        ->assertRedirect(route('login'));
});

// ── Individual accounts: 403 ──────────────────────────────────────────────────

test('individual user gets 403 on workspace index', function () {
    $user = User::factory()->create(['account_type' => 'individual']);

    $this->actingAs($user)
        ->get(route('workspaces.index'))
        ->assertForbidden();
});

test('individual user gets 403 when trying to create workspace', function () {
    $user = User::factory()->create(['account_type' => 'individual']);

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.store'), ['name' => 'Staging'])
        ->assertForbidden();
});

// ── Company accounts: workspace index ────────────────────────────────────────

test('company user can view workspace index', function () {
    $user = User::factory()->company()->create();

    $this->actingAs($user)
        ->get(route('workspaces.index'))
        ->assertOk();
});

// ── Company accounts: create workspace ───────────────────────────────────────

test('company user can create a workspace', function () {
    $user = User::factory()->company()->create();

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.store'), [
            'name'        => 'Staging',
            'environment' => 'staging',
        ])
        ->assertRedirect(route('workspaces.index'));

    $this->assertDatabaseHas('teams', [
        'name'   => 'Staging',
        'status' => 'active',
    ]);
});

test('workspace name is required', function () {
    $user = User::factory()->company()->create();

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

// ── switch workspace ──────────────────────────────────────────────────────────

test('user can switch to a workspace they belong to', function () {
    $user = User::factory()->company()->create();

    $second = Team::factory()->create(['status' => 'active']);
    $second->memberships()->create([
        'user_id' => $user->id,
        'role'    => TeamRole::Member->value,
    ]);

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.switch', $second))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->current_team_id)->toBe($second->id);
});

test('user cannot switch to a workspace they do not belong to', function () {
    $user  = User::factory()->company()->create();
    $other = Team::factory()->create(['status' => 'active']);

    $originalTeamId = $user->current_team_id;

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.switch', $other));

    expect($user->fresh()->current_team_id)->toBe($originalTeamId);
});

// ── rename workspace ──────────────────────────────────────────────────────────

test('owner can rename a workspace', function () {
    $user      = User::factory()->company()->create();
    $workspace = $user->currentTeam;

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->patch(route('workspaces.update', $workspace), ['name' => 'Renamed'])
        ->assertRedirect();

    expect($workspace->fresh()->name)->toBe('Renamed');
});

test('member cannot rename a workspace', function () {
    $owner = User::factory()->company()->create();
    $ws    = $owner->currentTeam;

    $member = User::factory()->create();
    $ws->memberships()->create([
        'user_id' => $member->id,
        'role'    => TeamRole::Member->value,
    ]);
    $member->forceFill(['current_team_id' => $ws->id])->save();

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($member)
        ->patch(route('workspaces.update', $ws), ['name' => 'Hacked'])
        ->assertForbidden();
});

// ── archive workspace ─────────────────────────────────────────────────────────

test('owner can archive a non-default workspace', function () {
    $user = User::factory()->company()->create();

    // Create a second non-locked workspace the user owns
    $ws = Team::factory()->create(['status' => 'active', 'is_personal' => false]);
    $ws->memberships()->create([
        'user_id' => $user->id,
        'role'    => TeamRole::Owner->value,
    ]);

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.archive', $ws))
        ->assertRedirect(route('workspaces.index'));

    expect($ws->fresh()->status)->toBe('archived');
});

test('default (locked) workspace cannot be archived', function () {
    $user    = User::factory()->company()->create();
    $default = $user->currentTeam; // is_personal = true → locked

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->actingAs($user)
        ->post(route('workspaces.archive', $default))
        ->assertForbidden();
});
