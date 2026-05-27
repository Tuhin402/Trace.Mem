<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_scope_id',
        'subscription_plan_id',

        'name',
        'key_prefix',
        'key_hash',
        'key_last4',

        'environment',
        'mode',

        'sandbox_only',
        'key_version',
        'issued_at',
        'last_rotated_at',
        'allowed_origins',
        'allowed_ips',

        'rate_limit_max_requests',
        'rate_limit_window_seconds',

        'usage_count',
        'last_used_at',
        'expires_at',
        'revoked_at',

        'scopes',
        'metadata',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'subscription_plan_id' => 'integer',

        'tenant_scope_id' => 'string',

        'environment' => 'string',
        'mode' => 'string',

        'sandbox_only' => 'boolean',
        'key_version' => 'integer',
        'issued_at' => 'datetime',
        'last_rotated_at' => 'datetime',
        'allowed_origins' => 'array',
        'allowed_ips' => 'array',

        'rate_limit_max_requests' => 'integer',
        'rate_limit_window_seconds' => 'integer',

        'usage_count' => 'integer',

        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',

        'scopes' => 'array',
        'metadata' => 'array',
    ];
    

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && now()->greaterThan($this->expires_at);
    }

    public function isTest(): bool
    {
        return $this->environment === 'test';
    }
    public function isSandbox(): bool
    {
        return $this->environment === 'test';
    }

    public function isLive(): bool
    {
        return $this->environment === 'live';
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];
        return in_array($scope, $scopes, true);
    }

    public function allowsAiFirst(): bool
    {
        return $this->mode === 'ai_first';
    }

    public function allowsSemanticOnly(): bool
    {
        return $this->mode === 'semantic_only';
    }

    public function touchUsage(): void
    {
        $this->forceFill([
            'usage_count' => ((int) $this->usage_count) + 1,
            'last_used_at' => now(),
        ])->save();
    }
}