<?php

namespace App\Services\Workspace;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\ApiKeyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkspaceService
{
    public function __construct(
        private readonly ApiKeyService $apiKeys,
        private readonly WorkspaceAuditService $audit,
    ) {}

    /**
     * Create the default workspace on registration.
     *
     * Individual → is_personal=true, name='Default'
     * Company    → is_personal=false, name='Default'
     *
     * Creates exactly ONE test API key.
     * No live key is created — live keys are created manually by the owner.
     *
     * Owner is recorded exclusively via team_members (role='owner').
     * No owner_user_id column — single source of truth.
     */
    public function createDefaultWorkspace(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $workspace = Team::create([
                'name'        => 'Default',
                'is_personal' => true,
                // 'is_personal' => $user->account_type === 'individual',
                'status'      => 'active',
                'settings'    => null,
                'features'    => null,
            ]);

            $workspace->memberships()->create([
                'user_id' => $user->id,
                'role'    => TeamRole::Owner->value,
            ]);

            // One test key only — no live key
            $plainKey = $this->autoCreateTestKey($user, $workspace);

            $this->audit->log($workspace, $user, 'workspace.created', [
                'is_personal' => $workspace->is_personal,
                'source'      => 'registration',
            ]);

            return [$workspace, $plainKey];
        });
    }

    /**
     * Create an additional workspace — Company owners only.
     *
     * Creates exactly ONE test API key.
     * Live keys are created manually later.
     */
    public function createWorkspace(
        User $user,
        string $name,
        ?string $purpose = null,
        ?string $environment = null,
    ): array {
        return DB::transaction(function () use ($user, $name, $purpose, $environment) {
            $workspace = Team::create([
                'name'        => $name,
                'is_personal' => false,
                'status'      => 'active',
                'purpose'     => $purpose,
                'environment' => $environment,
                'settings'    => null,
                'features'    => null,
            ]);

            $workspace->memberships()->create([
                'user_id' => $user->id,
                'role'    => TeamRole::Owner->value,
            ]);

            // One test key only
            $plainKey = $this->autoCreateTestKey($user, $workspace);

            $this->audit->log($workspace, $user, 'workspace.created', [
                'purpose'     => $purpose,
                'environment' => $environment,
                'source'      => 'manual',
            ]);

            return [$workspace, $plainKey];
        });
    }

    /**
     * Create exactly one test API key for a workspace on creation.
     *
     * Failure is non-fatal — logged, never thrown.
     * The workspace is still usable; owner can create keys manually.
     */
    public function autoCreateTestKey(User $user, Team $workspace): ?string
    {
        try {
            $result = $this->apiKeys->createForUser(
                user: $user,
                name: 'Default Test Key',
                environment: 'test',
                options: [],
                replacing: null,
                workspace: $workspace,
            );
            return $result['plain_key'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('WorkspaceService: auto test-key creation failed', [
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'error'        => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Rename a workspace.
     *
     * Rules:
     *  - Default (is_personal=true) workspace cannot be renamed to an empty string.
     *  - Any other workspace can be renamed freely.
     */
    public function renameWorkspace(User $user, Team $workspace, string $name): void
    {
        if ($workspace->isLocked() && blank($name)) {
            abort(422, 'The default workspace cannot be unnamed.');
        }

        $oldName = $workspace->name;
        $workspace->forceFill(['name' => trim($name)])->save();

        $this->audit->log($workspace, $user, 'workspace.renamed', [
            'old_name' => $oldName,
            'new_name' => $workspace->name,
        ]);
    }

    /**
     * Archive a workspace.
     *
     * Rules:
     *  - Default (is_personal=true) workspace can NEVER be archived.
     *  - Archiving does not delete keys or memories.
     */
    public function archiveWorkspace(User $user, Team $workspace): void
    {
        abort_if($workspace->isLocked(), 422, 'The default workspace cannot be archived.');

        $workspace->forceFill(['status' => 'archived'])->save();

        $this->audit->log($workspace, $user, 'workspace.archived');
    }

    /**
     * Remove a member from a workspace.
     *
     * Rules:
     *  - Cannot remove the last owner from a workspace.
     *  - Does NOT auto-switch the removed user's current_team_id.
     *    If they have no remaining active workspaces, they will see a "no workspace" state.
     *  - Sets current_team_id = null if the removed user's active workspace was this one.
     */
    public function removeMember(User $actor, Team $workspace, User $target): void
    {
        // Guard: cannot remove the only owner
        $ownerCount = $workspace->memberships()
            ->where('role', TeamRole::Owner->value)
            ->count();

        if ($ownerCount <= 1 && $target->ownsTeam($workspace)) {
            abort(422, 'Cannot remove the only owner of a workspace.');
        }

        $workspace->members()->detach($target->id);

        // Nullify current_team_id if it pointed to this workspace
        // We do NOT auto-switch — no remaining workspaces = no workspace state
        if ($target->current_team_id === $workspace->id) {
            $target->forceFill(['current_team_id' => null])->save();
        }

        $this->audit->logWithSubject(
            workspace:   $workspace,
            actor:       $actor,
            action:      'member.removed',
            subjectType: 'user',
            subjectId:   $target->id,
            metadata:    ['user_id' => $target->id, 'email' => $target->email],
        );
    }
}
