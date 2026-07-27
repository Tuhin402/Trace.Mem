<?php

namespace App\Services\Control\Platform\Identity;

use App\DTOs\Control\Platform\Identity\WorkspaceListDTO;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkspaceQueryService
{
    /**
     * Retrieve a paginated, filtered, and sorted list of Workspaces.
     */
    public function getPaginatedList(Request $request): LengthAwarePaginator
    {
        $query = Team::query()
            ->with(['tenant'])
            ->withCount(['members']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('tenant_scope_id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($tenant = $request->input('tenant')) {
            $query->whereHas('tenant', function ($q) use ($tenant) {
                $q->where('slug', $tenant)->orWhere('id', $tenant);
            });
        }

        if ($environment = $request->input('environment')) {
            $query->where('environment', $environment);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Whitelist sort fields
        $allowedSorts = ['name', 'created_at', 'status', 'environment'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 25);
        
        $paginator = $query->paginate($perPage)->withQueryString();

        // Transform collection to DTOs
        $paginator->getCollection()->transform(function ($team) {
            return WorkspaceListDTO::fromModel($team);
        });

        return $paginator;
    }

    /**
     * Retrieve a highly detailed profile for a single workspace.
     */
    public function getProfile(string $tenantSlug, string $workspaceSlug): \App\DTOs\Control\Platform\Identity\WorkspaceProfileDTO
    {
        $workspace = Team::query()
            ->with(['tenant', 'members' => function ($q) {
                $q->limit(10);
            }, 'apiKeys' => function ($q) {
                $q->latest()->limit(5);
            }, 'auditLogs' => function ($q) {
                $q->with('user')->latest()->limit(20);
            }])
            ->withCount(['members', 'apiKeys', 'memories'])
            ->whereHas('tenant', function ($q) use ($tenantSlug) {
                $q->where('slug', $tenantSlug);
            })
            ->where('slug', $workspaceSlug)
            ->firstOrFail();

        return \App\DTOs\Control\Platform\Identity\WorkspaceProfileDTO::fromModel($workspace);
    }
}
