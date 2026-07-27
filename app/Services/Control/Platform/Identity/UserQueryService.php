<?php

namespace App\Services\Control\Platform\Identity;

use App\DTOs\Control\Platform\Identity\UserListDTO;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UserQueryService
{
    /**
     * Retrieve a paginated, filtered, and sorted list of Users.
     */
    public function getPaginatedList(Request $request): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['tenant'])
            ->withCount(['teams']); // Using teams_count as proxy for workspaces

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tenant_scope_id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->input('status')) {
            // Note: Since User currently lacks a dedicated 'status' column, 
            // we'll emulate it or add it later. For now we ignore or map if needed.
            // Example mapping if we had it: $query->where('status', $status);
        }

        if ($tenant = $request->input('tenant')) {
            $query->whereHas('tenant', function ($q) use ($tenant) {
                $q->where('slug', $tenant)->orWhere('id', $tenant);
            });
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Whitelist sort fields to prevent SQL injection
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 25);
        
        $paginator = $query->paginate($perPage)->withQueryString();

        // Transform collection to DTOs
        $paginator->getCollection()->transform(function ($user) {
            return UserListDTO::fromModel($user);
        });

        return $paginator;
    }

    /**
     * Retrieve a highly detailed profile for a single user.
     */
    public function getProfile(string $uuid): \App\DTOs\Control\Platform\Identity\UserProfileDTO
    {
        $user = User::query()
            ->with(['tenant', 'teams', 'subscriptions'])
            ->withCount(['teams', 'apiKeys', 'memories'])
            ->where('tenant_scope_id', $uuid)
            ->orWhere('id', $uuid) // Support legacy ID routing just in case
            ->firstOrFail();

        return \App\DTOs\Control\Platform\Identity\UserProfileDTO::fromModel($user);
    }
}
