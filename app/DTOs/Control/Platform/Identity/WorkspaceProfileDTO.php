<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\Team;

readonly class WorkspaceProfileDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $status,
        public string $environment,
        public array $tenant,
        public array $metrics,
        public array $members,
        public array $recent_api_keys,
        public array $recent_activity,
        public string $created_at
    ) {}

    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            status: $team->status ?? 'active',
            environment: $team->environment ?? 'production',
            tenant: [
                'id' => $team->tenant?->id,
                'name' => $team->tenant?->name ?? 'Unknown',
                'slug' => $team->tenant?->slug ?? 'unknown',
            ],
            metrics: [
                'members' => $team->members_count ?? 0,
                'api_keys' => $team->api_keys_count ?? 0,
                'memories' => $team->memories_count ?? 0,
            ],
            members: $team->members->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'uuid' => $user->tenant_scope_id,
                'role' => $user->pivot->role ?? 'member',
            ])->toArray(),
            recent_api_keys: $team->apiKeys->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'last_used_at' => $key->last_used_at?->diffForHumans() ?? 'Never',
                'is_active' => $key->is_active,
            ])->toArray(),
            recent_activity: $team->auditLogs?->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->actor?->name ?? 'System',
                'created_at' => $log->created_at?->diffForHumans() ?? 'Unknown',
            ])->toArray() ?? [],
            created_at: $team->created_at->format('M j, Y')
        );
    }
}
