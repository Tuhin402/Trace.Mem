<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\Tenant;

readonly class TenantListDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $status,
        public string $plan,
        public string $created_at,
        public int $user_count,
        public int $workspace_count
    ) {}

    public static function fromModel(Tenant $tenant): self
    {
        return new self(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            status: $tenant->status ?? 'active',
            plan: $tenant->plan ?? 'Legacy',
            created_at: $tenant->created_at->format('M j, Y'),
            user_count: $tenant->users_count ?? 0,
            workspace_count: $tenant->teams_count ?? 0 // assuming teams_count for workspaces
        );
    }
}
