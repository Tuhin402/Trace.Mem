<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner     = 'owner';
    case Admin     = 'admin';
    case Member    = 'member';
    case Developer = 'developer';
    case Viewer    = 'viewer';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner     => 'Owner',
            self::Admin     => 'Admin',
            self::Member    => 'Member',
            self::Developer => 'Developer',
            self::Viewer    => 'Viewer',
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::AddMember,
                TeamPermission::UpdateMember,
                TeamPermission::RemoveMember,
            ],
            self::Developer => [
                TeamPermission::CreateApiKey,
                TeamPermission::ViewApiKey,
                TeamPermission::ViewUsage,
            ],
            self::Member => [
                TeamPermission::ViewApiKey,
                TeamPermission::ViewUsage,
            ],
            self::Viewer => [
                TeamPermission::ViewApiKey,
            ],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner     => 5,
            self::Admin     => 4,
            self::Developer => 3,
            self::Member    => 2,
            self::Viewer    => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
