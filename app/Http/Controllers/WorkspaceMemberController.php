<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkspaceMemberController extends Controller
{
    /**
     * Display the workspace members and invitations page.
     */
    public function index(Request $request, Team $workspace)
    {
        Gate::authorize('view', $workspace); // Or rely on middleware to scope workspace

        // Ensure user can manage members or at least view them
        // The UI will handle hiding the "Remove" and "Invite" buttons based on permissions
        
        $members = $workspace->memberships()
            ->with('user:id,name,email,created_at')
            ->get()
            ->map(function ($membership) {
                return [
                    'id'       => $membership->user_id,
                    'name'     => $membership->user?->name,
                    'email'    => $membership->user?->email,
                    'role'     => $membership->role->value,
                    'joined_at'=> $membership->created_at->toIso8601String(),
                ];
            });

        $invitations = $workspace->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(function ($invitation) {
                return [
                    'id'         => $invitation->id,
                    'email'      => $invitation->email,
                    'role'       => $invitation->role->value,
                    'created_at' => $invitation->created_at->toIso8601String(),
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'is_expired' => $invitation->isExpired(),
                ];
            });

        return \Inertia\Inertia::render('app/WorkspaceMembers', [
            'targetWorkspace' => [
                'id'   => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'members'     => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Remove the specified member from the workspace.
     */
    public function destroy(Request $request, Team $workspace, int $userId)
    {
        $currentUser = $request->user();

        // Check permission
        if (!$currentUser->toTeamPermissions($workspace)->canRemoveMember) {
            abort(403, 'You do not have permission to remove members.');
        }

        // Prevent removing the owner
        $role = $workspace->memberships()->where('user_id', $userId)->first()?->role;
        if ($role === \App\Enums\TeamRole::Owner) {
            return back()->with('error', 'The workspace owner cannot be removed.');
        }

        // Remove the member
        $workspace->members()->detach($userId);

        // If the removed user is currently using this workspace, we need to handle their session on their next request.
        // In our HandleInertiaRequests, NoWorkspaceException will automatically assign them a fallback team.
        // We also need to clear caches for the removed user.
        $removedUser = User::find($userId);
        if ($removedUser) {
            app(\App\Services\Workspace\WorkspaceContextService::class)->clearWorkspaceCaches($removedUser);
            
            // If it was their current team, clear it so they fall back to another one.
            if ($removedUser->current_team_id === $workspace->id) {
                $removedUser->forceFill(['current_team_id' => null])->save();
            }
        }

        return back()->with('message', 'Member removed successfully.');
    }
}
