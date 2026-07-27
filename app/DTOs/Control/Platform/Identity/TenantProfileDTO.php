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
        public array $subscriptions,
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
            recent_users: $tenant->users->take(10)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'uuid' => $user->tenant_scope_id,
                'created_at' => $user->created_at->format('M j, Y'),
            ])->toArray(),
            subscriptions: $tenant->users->flatMap(function ($user) {
                return $user->subscriptions->map(fn ($sub) => [
                    'id' => $sub->id,
                    'user_name' => $user->name,
                    'plan' => $sub->plan_id,
                    'status' => $sub->cancelled_at ? 'cancelled' : ($sub->is_active ? 'active' : 'inactive'),
                    'started_at' => $sub->starts_at?->format('M j, Y'),
                    'cancelled_at' => $sub->cancelled_at?->format('M j, Y'),
                ]);
            })->sortByDesc('started_at')->values()->toArray(),
            created_at: $tenant->created_at->format('M j, Y')
        );
    }
}
