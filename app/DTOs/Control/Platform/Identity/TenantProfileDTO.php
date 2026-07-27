<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\Tenant;

readonly class TenantProfileDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $status,
        public string $plan,
        public array $metrics,
        public array $workspaces,
        public array $recent_users,
        public string $created_at
    ) {}

    public static function fromModel(Tenant $tenant): self
    {
        return new self(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            status: $tenant->status ?? 'active',
            plan: $tenant->plan ?? 'Legacy',
            metrics: [
                'users' => $tenant->users_count ?? 0,
                'workspaces' => $tenant->workspaces_count ?? 0,
            ],
            workspaces: $tenant->workspaces->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'status' => $team->status ?? 'active',
                'user_count' => $team->members_count ?? 0,
            ])->toArray(),
            recent_users: $tenant->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'uuid' => $user->tenant_scope_id,
            ])->toArray(),
            created_at: $tenant->created_at->format('M j, Y')
        );
    }
}
