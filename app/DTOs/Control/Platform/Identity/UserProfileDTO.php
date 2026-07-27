<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\User;

readonly class UserProfileDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $email,
        public string $status,
        public array $tenant,
        public array $workspaces,
        public array $subscription,
        public array $metrics,
        public string $created_at,
        public ?string $last_login_at,
        public bool $is_verified,
        public bool $has_2fa
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            uuid: $user->tenant_scope_id ?? 'legacy-'.$user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status ?? 'active',
            tenant: [
                'id' => $user->tenant?->id,
                'name' => $user->tenant?->name ?? 'Unknown',
                'slug' => $user->tenant?->slug ?? 'unknown',
            ],
            workspaces: $user->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'role' => $team->pivot->role ?? 'member',
            ])->toArray(),
            subscription: [
                'plan' => $user->currentSubscription?->plan_id ?? 'Free',
                'status' => $user->currentSubscription?->is_active ? 'active' : 'inactive',
            ],
            metrics: [
                'api_keys' => $user->api_keys_count ?? 0,
                'memories' => $user->teams ? $user->teams->sum('memories_count') : 0,
                'workspaces' => $user->teams_count ?? 0,
            ],
            created_at: $user->created_at->format('M j, Y'),
            last_login_at: $user->last_login_at?->diffForHumans() ?? 'Never',
            is_verified: $user->email_verified_at !== null,
            has_2fa: !empty($user->two_factor_secret)
        );
    }
}
