<?php

namespace App\Services\Control\Platform\Identity;

use App\DTOs\Control\Platform\Identity\TenantListDTO;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use App\Services\Control\Overview\CacheTiers;

class TenantQueryService
{
    /**
     * Retrieve a paginated, filtered, and sorted list of Tenants.
     */
    public function getPaginatedList(Request $request): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->withCount(['users', 'workspaces']); // Assuming relationships exist for users and workspaces

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Whitelist sort fields
        $allowedSorts = ['name', 'created_at', 'status', 'plan'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 25);
        
        $paginator = $query->paginate($perPage)->withQueryString();

        // Transform collection to DTOs
        $paginator->getCollection()->transform(function ($tenant) {
            return TenantListDTO::fromModel($tenant);
        });

        return $paginator;
    }

    public function getProfile(string $slug): \App\DTOs\Control\Platform\Identity\TenantProfileDTO
    {
        $tenant = Tenant::query()
            ->with(['workspaces' => function ($q) {
                $q->withCount('members');
            }, 'users' => function ($q) {
                $q->with('subscriptions.subscriptionPlan');
            }])
            ->withCount(['workspaces', 'users'])
            ->where('slug', $slug)
            ->firstOrFail();

        return \App\DTOs\Control\Platform\Identity\TenantProfileDTO::fromModel($tenant);
    }
}
