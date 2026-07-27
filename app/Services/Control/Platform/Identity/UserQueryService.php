<?php

namespace App\Services\Control\Platform\Identity;

use App\DTOs\Control\Platform\Identity\UserListDTO;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use App\Services\Control\Overview\CacheTiers;

class UserQueryService
{
    /**
     * Retrieve a paginated, filtered, and sorted list of Users.
     */
    public function getPaginatedList(Request $request): LengthAwarePaginator
    {
        $cacheKey = 'control:users:list:' . md5($request->fullUrl());

        return Cache::remember($cacheKey, CacheTiers::NEAR_REAL_TIME->value, function () use ($request) {
            $query = User::query()
                ->with(['tenant']) // Used for organization badge
                ->withCount(['teams as workspaces_count']); 

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhere('tenant_scope_id', 'like', "%{$search}%");
                });
            }

            // Filters
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($role = $request->input('role')) {
                $query->where('platform_role', $role);
            }

            // Sorting
            $sortField = $request->input('sort', 'created_at');
            $sortDirection = $request->input('direction', 'desc');

            // Whitelist sort fields
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
        });
    }

    /**
     * Retrieve a highly detailed profile for a single user.
     */
    public function getProfile(string $uuid): \App\DTOs\Control\Platform\Identity\UserProfileDTO
    {
        $cacheKey = 'control:users:profile:' . $uuid;

        return Cache::remember($cacheKey, CacheTiers::NEAR_REAL_TIME->value, function () use ($uuid) {
            $query = User::query()
                ->with(['tenant', 'subscriptions.subscriptionPlan', 'freeTrialEvents', 'teams' => function ($q) {
                    $q->withCount('memories');
                }, 'activityLogs' => function ($q) {
                    $q->latest()->limit(20);
                }])
                ->withCount(['teams', 'apiKeys']);

            if (\Illuminate\Support\Str::isUuid($uuid)) {
                $query->where('tenant_scope_id', $uuid);
            } else {
                $id = str_replace('legacy-', '', $uuid);
                $query->where('id', $id);
            }

            $user = $query->firstOrFail();

            return \App\DTOs\Control\Platform\Identity\UserProfileDTO::fromModel($user);
        });
    }
}
