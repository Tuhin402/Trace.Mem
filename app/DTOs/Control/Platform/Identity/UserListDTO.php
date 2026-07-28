<?php

namespace App\DTOs\Control\Platform\Identity;

use App\Models\User;
use Illuminate\Support\Carbon;

readonly class UserListDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $email,
        public string $status,
        public ?string $tenant_name,
        public ?string $tenant_slug,
        public ?string $tenant_id,
        public ?string $last_active_at,
        public string $created_at,
        public bool $is_verified,
        public bool $has_2fa,
        public int $workspace_count,
        public bool $has_billing_override = false
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            uuid: $user->uuid ?? 'legacy-'.$user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status ?? 'active',
            tenant_name: $user->tenant?->name ?? 'Unknown',
            tenant_slug: $user->tenant?->slug ?? 'unknown',
            tenant_id: $user->tenant_scope_id,
            last_active_at: $user->last_login_at?->diffForHumans() ?? 'Never',
            created_at: $user->created_at->format('M j, Y'),
            is_verified: $user->email_verified_at !== null,
            has_2fa: !empty($user->two_factor_secret),
            workspace_count: $user->teams_count ?? 0,
            has_billing_override: $user->relationLoaded('billingOverrides') && $user->billingOverrides->isNotEmpty()
        );
    }
}
