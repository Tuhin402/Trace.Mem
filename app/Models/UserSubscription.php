<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'billing_cycle',
        'status',
        'is_active',
        'starts_at',
        'renews_at',
        'ends_at',
        'auto_renew',
        'overage_enabled',
        'quotas_snapshot',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'user_id'              => 'integer',
        'subscription_plan_id' => 'integer',

        'billing_cycle' => 'string',
        'status'        => 'string',

        'starts_at'    => 'datetime',
        'renews_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'cancelled_at' => 'datetime',

        'auto_renew'       => 'boolean',
        'is_active'        => 'boolean',
        'overage_enabled'  => 'boolean',

        'quotas_snapshot' => 'array',
    ];

    /* ── Helpers ─────────────────────────────────────────────── */

    /**
     * Returns true if the user explicitly cancelled this subscription.
     * A cancelled subscription denies access immediately regardless of ends_at.
     */
    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    /**
     * A subscription is entitlement-eligible only if it is active
     * AND has not been explicitly cancelled by the user.
     */
    public function isEntitlementActive(): bool
    {
        return $this->is_active && ! $this->isCancelled();
    }

    /* ── Relations ───────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}