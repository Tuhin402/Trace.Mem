<?php

namespace App\Http\Middleware;

use App\Exceptions\NoWorkspaceException;
use App\Services\Workspace\WorkspaceContextService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly WorkspaceContextService $workspaceContext,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            // Domain constants — used by frontend for OG tags and API snippet URLs.
            // Values come from config/domains.php → .env — never hardcoded.
            'domains' => [
                'root' => config('domains.root'),
                'app'  => config('domains.app'),
                'api'  => config('domains.api'),
            ],

            // Workspace context — only shared for authenticated users.
            // Individual accounts: workspace = null (no workspace UI shown).
            // Company accounts: workspace = current workspace, workspaces = all workspaces.
            'workspace' => $user ? $this->resolveCurrentWorkspaceProps($user) : null,
            'account'   => $user ? [
                'type'         => $user->account_type,
                'isIndividual' => $user->account_type === 'individual',
                'isCompany'    => $user->account_type === 'tenant',
            ] : null,
        ];
    }

    /**
     * Resolve workspace props for the current authenticated user.
     * Returns null for Individual accounts (no workspace UI).
     */
    private function resolveCurrentWorkspaceProps($user): ?array
    {
        // Individual accounts: no workspace UI — return null to hide workspace elements
        if ($this->workspaceContext->isIndividual($user)) {
            return null;
        }

        try {
            $workspace = $this->workspaceContext->current($user);

            return [
                'id'          => $workspace->id,
                'name'        => $workspace->name,
                'slug'        => $workspace->slug,
                'status'      => $workspace->status,
                'environment' => $workspace->environment,
                'isDefault'   => $workspace->isDefault(),
                'isLocked'    => $workspace->isLocked(),
                'memberships' => $workspace->memberships()
                    ->with('user:id,name,email')
                    ->get()
                    ->map(fn ($m) => [
                        'user_id' => $m->user_id,
                        'name'    => $m->user?->name,
                        'email'   => $m->user?->email,
                        'role'    => $m->role?->value,
                    ]),
                // All workspaces for the switcher — only for Company accounts
                'all' => $user->teams()
                    ->where('teams.status', 'active')
                    ->whereNull('teams.deleted_at')
                    ->get()
                    ->map(fn ($w) => [
                        'id'        => $w->id,
                        'name'      => $w->name,
                        'slug'      => $w->slug,
                        'isCurrent' => $w->id === $workspace->id,
                    ]),
            ];
        } catch (NoWorkspaceException) {
            // User has been removed from all workspaces — return a sentinel
            return ['id' => null, 'name' => null, 'slug' => null, 'status' => 'none'];
        }
    }
}
