<?php

namespace App\Http\Controllers\Control\Platform\Identity;

use App\Http\Controllers\Controller;
use App\Services\Control\Platform\Identity\WorkspaceQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(
        private WorkspaceQueryService $workspaceQueryService
    ) {}

    public function index(Request $request): Response
    {
        $workspaces = $this->workspaceQueryService->getPaginatedList($request);

        return Inertia::render('control/platform/identity/workspaces/Index', [
            'workspaces' => $workspaces,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'tenant', 'environment']),
        ]);
    }

    public function show(string $tenantSlug, string $workspaceSlug): Response
    {
        $workspace = $this->workspaceQueryService->getProfile($tenantSlug, $workspaceSlug);

        return Inertia::render('control/platform/identity/workspaces/Profile', [
            'workspace' => $workspace,
        ]);
    }
}
