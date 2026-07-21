<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_personal',
        // ── Workspace fields (Phase B) ────────────────────────────────────────
        'status',      // 'active' | 'archived' | 'suspended'
        'purpose',
        'environment', // 'development' | 'staging' | 'production' | 'testing'
        'settings',    // jsonb
        'features',    // jsonb
        // ── Workspace limits (all nullable = inherit from subscription) ────────
        'max_api_keys',
        'max_memory_count',
        'max_requests_per_month',
        'max_storage_bytes',
        'max_token_usage',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
            // Ensure all new workspaces start with 'active' status
            $team->status ??= 'active';
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal'            => 'boolean',
            'settings'               => 'array',
            'features'               => 'array',
            'status'                 => 'string',
            'max_api_keys'           => 'integer',
            'max_memory_count'       => 'integer',
            'max_requests_per_month' => 'integer',
            'max_storage_bytes'      => 'integer',
            'max_token_usage'        => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the team owner (derived from team_members pivot — single source of truth).
     * No owner_user_id column exists on teams.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the API keys scoped to this workspace.
     *
     * @return HasMany<ApiKey, $this>
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'workspace_id');
    }

    /**
     * Get the memories scoped to this workspace.
     *
     * @return HasMany<Memory, $this>
     */
    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class, 'workspace_id');
    }

    /**
     * Get the audit logs for this workspace.
     *
     * @return HasMany<WorkspaceAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(WorkspaceAuditLog::class, 'workspace_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Workspace guard helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether this is a default (personal) workspace.
     * Default workspaces cannot be deleted, archived, suspended, or renamed to empty.
     */
    public function isDefault(): bool
    {
        return (bool) $this->is_personal;
    }

    /**
     * Locked workspaces (default/personal) are protected from destructive operations.
     */
    public function isLocked(): bool
    {
        return $this->is_personal;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
