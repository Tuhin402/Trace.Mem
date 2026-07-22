<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\Workspace\WorkspaceContextService;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly WorkspaceContextService $workspaceContext,
    ) {}

    /**
     * List all active workspaces for the authenticated user (Company accounts only).
     * Individual accounts don't have the workspace UI — return 403.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($this->workspaceContext->isIndividual($user)) {
            abort(403, 'Workspace management is only available for Company accounts.');
        }

        $workspaces = $user->teams()
            ->where('teams.status', '!=', 'archived')
            ->whereNull('teams.deleted_at')
            ->orderByRaw('teams.is_personal DESC')
            ->orderBy('teams.name')
            ->get()
            ->map(fn (Team $w) => [
                'id'          => $w->id,
                'name'        => $w->name,
                'slug'        => $w->slug,
                'status'      => $w->status,
                'environment' => $w->environment,
                'purpose'     => $w->purpose,
                'isDefault'   => $w->isDefault(),
                'isLocked'    => $w->isLocked(),
                'isCurrent'   => $user->current_team_id === $w->id,
                'memberCount' => $w->memberships()->count(),
            ]);

        return Inertia::render('app/Workspaces', [
            'workspaces' => $workspaces,
            'flash'      => [
                'message' => session('message'),
                'error'   => session('error'),
            ],
        ]);
    }

    /**
     * Create a new workspace (Company accounts only).
     * Creates exactly one test API key. No live key is created automatically.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($this->workspaceContext->isIndividual($user)) {
            abort(403, 'Workspace management is only available for Company accounts.');
        }

        Gate::authorize('create', Team::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'purpose'     => ['nullable', 'string', 'max:500'],
            'environment' => ['nullable', 'in:development,staging,production,testing'],
        ]);

        [$workspace, $plainKey] = $this->workspaceService->createWorkspace(
            user:        $user,
            name:        $data['name'],
            purpose:     $data['purpose'] ?? null,
            environment: $data['environment'] ?? null,
        );

        $redirect = redirect()->route('workspaces.index')
            ->with('message', "Workspace \"{$workspace->name}\" created. A test key has been generated automatically.");
        
        if (!empty($plainKey)) {
            $redirect->with('plain_key', $plainKey);
        }

        return $redirect;
    }

    /**
     * Switch the current workspace.
     */
    public function switch(Request $request, Team $workspace)
    {
        $user = $request->user();

        $switched = $this->workspaceContext->switchTo($user, $workspace);

        if (! $switched) {
            return back()->with('error', 'You do not have access to that workspace.');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Rename a workspace.
     */
    public function update(Request $request, Team $workspace)
    {
        Gate::authorize('update', $workspace);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->workspaceService->renameWorkspace($request->user(), $workspace, $data['name']);

        return back()->with('message', 'Workspace renamed.');
    }

    /**
     * Archive a workspace.
     * Default workspaces are locked — cannot be archived.
     */
    public function archive(Request $request, Team $workspace)
    {
        Gate::authorize('archive', $workspace);

        $this->workspaceService->archiveWorkspace($request->user(), $workspace);

        // If user's current workspace was just archived, clear current_team_id
        $user = $request->user();
        if ($user->current_team_id === $workspace->id) {
            $fallback = $user->teams()
                ->where('teams.status', 'active')
                ->whereNull('teams.deleted_at')
                ->where('teams.id', '!=', $workspace->id)
                ->orderByRaw('teams.is_personal DESC')
                ->first();

            $user->forceFill(['current_team_id' => $fallback?->id])->save();
        }

        return redirect()
            ->route('workspaces.index')
            ->with('message', 'Workspace archived.');
    }
}
