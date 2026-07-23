<?php

namespace App\Http\Controllers;

use App\Enums\EmailTemplate;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Jobs\SendEmailJob;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceInvitationController extends Controller
{
    /**
     * Invite a new member to the workspace.
     */
    public function store(Request $request, Team $workspace)
    {
        Gate::authorize('update', $workspace); // Basic check, but we rely on permissions
        
        $user = $request->user();
        if (!$user->toTeamPermissions($workspace)->canCreateInvitation) {
            abort(403, 'You do not have permission to invite members.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', Rule::in(array_column(TeamRole::assignable(), 'value'))],
        ]);

        $email = Str::lower($validated['email']);

        // Check if user is already a member
        $existingMember = $workspace->members()->where('email', $email)->first();
        if ($existingMember) {
            return back()->with('error', 'This user is already a member of the workspace.');
        }

        // Check if an invitation already exists
        $existingInvitation = $workspace->invitations()->where('email', $email)->first();
        if ($existingInvitation && !$existingInvitation->isExpired()) {
            return back()->with('error', 'An active invitation has already been sent to this email address.');
        }

        // Create the invitation
        $invitation = $workspace->invitations()->updateOrCreate(
            ['email' => $email],
            [
                'role'       => $validated['role'],
                'invited_by' => $user->id,
                'expires_at' => now()->addDays(7),
                'code'       => Str::random(64), // Force new code if updating
            ]
        );

        // Send email
        $acceptUrl = url(route('workspaces.invitations.accept', ['code' => $invitation->code], absolute: false));

        SendEmailJob::dispatch(
            template:       EmailTemplate::WorkspaceInvitation,
            data:           [
                'inviter_name'   => $user->name,
                'workspace_name' => $workspace->name,
                'role_label'     => $invitation->role->label(),
                'accept_url'     => $acceptUrl,
            ],
            recipientEmail: $invitation->email,
            userId:         null,
            requestId:      Str::uuid()->toString(),
        );

        return back()->with('message', 'Invitation sent successfully.');
    }

    /**
     * Accept the given workspace invitation.
     */
    public function accept(Request $request, string $code)
    {
        $invitation = TeamInvitation::where('code', $code)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')->with('message', 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('home')->with('error', 'This invitation has expired.');
        }

        // Store code in session for seamless join after auth
        $request->session()->put('invitation_code', $code);

        if (!$request->user()) {
            // Check if user exists to redirect to login vs register
            $userExists = \App\Models\User::where('email', $invitation->email)->exists();
            if ($userExists) {
                return redirect()->route('login')->with('message', 'Please login to accept your workspace invitation.');
            } else {
                return redirect()->route('register', ['email' => $invitation->email])->with('message', 'Please create an account to accept your workspace invitation.');
            }
        }

        // If authenticated, process acceptance immediately
        $user = $request->user();

        // Warning if emails don't match, but allow anyway? Or enforce email matching?
        // Usually, we just accept the invite for the currently logged-in user.

        $workspace = $invitation->team;

        // Attach member
        if (!$user->belongsToTeam($workspace)) {
            $workspace->members()->attach($user->id, ['role' => $invitation->role->value]);
        } else {
            // Update role if already member but accepting invite for different role?
            // Optional: $workspace->members()->updateExistingPivot($user->id, ['role' => $invitation->role->value]);
        }

        // Mark as accepted
        $invitation->update(['accepted_at' => now()]);
        
        // Clear session
        $request->session()->forget('invitation_code');

        // Switch workspace to the joined one
        app(\App\Services\Workspace\WorkspaceContextService::class)->switchTo($user, $workspace);

        return redirect()->route('dashboard')->with('message', "You have joined the {$workspace->name} workspace!");
    }

    /**
     * Cancel an existing workspace invitation.
     */
    public function destroy(Request $request, Team $workspace, string $invitationId)
    {
        $user = $request->user();
        if (!$user->toTeamPermissions($workspace)->canCancelInvitation) {
            abort(403, 'You do not have permission to cancel invitations.');
        }

        $invitation = $workspace->invitations()->findOrFail($invitationId);
        $invitation->delete();

        return back()->with('message', 'Invitation cancelled.');
    }
}
