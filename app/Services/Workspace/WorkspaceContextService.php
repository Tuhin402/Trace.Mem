<?php

namespace App\Services\Workspace;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Exceptions\NoWorkspaceException;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\SubscriptionCacheService;
use App\Support\TeamPermissions;
use Illuminate\Support\Facades\Log;

/**
 * WorkspaceContextService — the single source of truth for workspace context.
 *
 * Every controller, middleware, and policy reads workspace context through
 * this service. No controller ever calls $user->currentTeam() directly.
 *
 * Key guarantees:
 *  - Always validates membership before returning a workspace.
 *  - Never trusts current_team_id blindly — validates on every call.
 *  - Falls back gracefully when current_team_id is stale or removed.
 *  - Centralizes all cache clearing on workspace switch.
 */
class WorkspaceContextService
{
    public function __construct(
        private readonly SubscriptionCacheService $subscriptionCache,
    ) {}

    /**
     * Resolve and validate the current workspace for an authenticated user.
     *
     * Resolution order:
     *  1. current_team_id → validate membership → return if valid
     *  2. Any active personal workspace (is_personal=true)
     *  3. Any other active workspace (any membership)
     *  4. Throw NoWorkspaceException
     *
     * @throws NoWorkspaceException
     */
    public function current(User $user): Team
    {
        // Step 1: Try current_team_id
        if ($user->current_team_id) {
            $workspace = Team::find($user->current_team_id);

            if ($workspace && !$workspace->isArchived() && !$workspace->isSuspended()) {
                if ($user->belongsToTeam($workspace)) {
                    return $workspace;
                }
            }

            // current_team_id is stale — clear it and fall through
            Log::info('WorkspaceContextService: stale current_team_id, resolving fallback', [
                'user_id'         => $user->id,
                'stale_team_id'   => $user->current_team_id,
            ]);
        }

        // Step 2+3: Find any active workspace the user belongs to
        $fallback = $user->teams()
            ->where('teams.status', 'active')
            ->whereNull('teams.deleted_at')
            ->orderByRaw('teams.is_personal DESC') // prefer personal workspace
            ->first();

        if (!$fallback) {
            // Update current_team_id to null so we don't keep querying
            $user->forceFill(['current_team_id' => null])->save();

            throw new NoWorkspaceException(
                "User #{$user->id} has no active workspace."
            );
        }

        // Silently update current_team_id to the valid fallback
        $user->forceFill(['current_team_id' => $fallback->id])->save();

        return $fallback;
    }

    /**
     * Get the current role of the user in their current workspace.
     */
    public function currentRole(User $user): ?TeamRole
    {
        try {
            return $user->teamRole($this->current($user));
        } catch (NoWorkspaceException) {
            return null;
        }
    }

    /**
     * Get the full permissions object for the user in their current workspace.
     */
    public function currentPermissions(User $user): TeamPermissions
    {
        try {
            return $user->toTeamPermissions($this->current($user));
        } catch (NoWorkspaceException) {
            // Return a zero-permissions object when user has no workspace
            return new TeamPermissions(
                canUpdateTeam:      false,
                canDeleteTeam:      false,
                canAddMember:       false,
                canUpdateMember:    false,
                canRemoveMember:    false,
                canCreateInvitation: false,
                canCancelInvitation: false,
            );
        }
    }

    /**
     * Check if the user can create API keys in their current workspace.
     */
    public function canCreateApiKey(User $user): bool
    {
        return $this->currentRole($user)?->hasPermission(TeamPermission::CreateApiKey) ?? false;
    }

    /**
     * Check if the user can view billing for their tenant.
     */
    public function canViewBilling(User $user): bool
    {
        return $this->currentRole($user)?->hasPermission(TeamPermission::ViewBilling) ?? false;
    }

    /**
     * Check if the user is the owner of their current workspace.
     */
    public function isOwner(User $user): bool
    {
        return $this->currentRole($user) === TeamRole::Owner;
    }

    /**
     * Check if the user is on an Individual account.
     * Individual accounts have a hidden default workspace (no workspace UI shown).
     */
    public function isIndividual(User $user): bool
    {
        return $user->account_type === 'individual';
    }

    /**
     * Switch the user's current workspace.
     *
     * Validates membership before switching.
     * Clears all stale caches after a successful switch.
     *
     * @return bool  true on success, false if user is not a member
     */
    public function switchTo(User $user, Team $workspace): bool
    {
        if (!$user->belongsToTeam($workspace)) {
            return false;
        }

        if ($workspace->isArchived() || $workspace->isSuspended()) {
            return false;
        }

        $user->forceFill(['current_team_id' => $workspace->id])->save();

        $this->clearWorkspaceCaches($user);

        return true;
    }

    /**
     * Clear all caches affected by a workspace switch or membership change.
     *
     * Called on: workspace switch, member removal, workspace status change.
     * Centralised here — never duplicated in controllers.
     */
    public function clearWorkspaceCaches(User $user): void
    {
        try {
            $this->subscriptionCache->forgetUserAnalytics($user);
            $this->subscriptionCache->forgetEntitlements($user);
        } catch (\Throwable $e) {
            Log::warning('WorkspaceContextService: cache clear failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
