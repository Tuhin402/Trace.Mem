<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\Team;

readonly class WorkspaceListDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $tenant_name,
        public string $tenant_slug,
        public string $status,
        public string $environment,
        public string $created_at,
        public int $user_count
    ) {}

    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            tenant_name: $team->tenant?->name ?? 'Unknown',
            tenant_slug: $team->tenant?->slug ?? 'unknown',
            status: $team->status ?? 'active',
            environment: $team->environment ?? 'production',
            created_at: $team->created_at->format('M j, Y'),
            user_count: $team->members_count ?? 0
        );
    }
}
