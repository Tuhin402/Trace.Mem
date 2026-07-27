<?php

namespace App\Http\Controllers\Control\Platform\Identity;

use App\Http\Controllers\Controller;
use App\Services\Control\Platform\Identity\TenantQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private TenantQueryService $tenantQueryService
    ) {}

    public function index(Request $request): Response
    {
        $tenants = $this->tenantQueryService->getPaginatedList($request);

        return Inertia::render('control/platform/identity/tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'plan']),
        ]);
    }

    public function show(string $slug): Response
    {
        $tenant = $this->tenantQueryService->getProfile($slug);

        return Inertia::render('control/platform/identity/tenants/Profile', [
            'tenant' => $tenant,
        ]);
    }
}
