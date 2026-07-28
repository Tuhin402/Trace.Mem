<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'base_mode',
        'memory_write_limit',
        'request_limit',
        'api_key_limit',
        'test_api_key_limit',
        'live_api_key_limit',
        'test_key_ttl_days',
        'live_key_ttl_days',
        'request_rate_limit_max_requests',
        'request_rate_limit_window_seconds',
        'test_rate_limit_max_requests',
        'test_rate_limit_window_seconds',
        'allow_test_keys',
        'allow_live_keys',
        'price_monthly',
        'price_quarterly',
        'price_yearly',
        'is_active',
        'razorpay_plan_ids', // JSON: {monthly: 'plan_xxx', quarterly: 'plan_yyy', yearly: 'plan_zzz'}
        'status',
        'visibility',
        'sort_order',
        'metadata',
        'notes',
        'archived_at',
    ];
    
    protected $casts = [
        'memory_write_limit' => 'integer',
        'request_limit' => 'integer',
        'api_key_limit' => 'integer',
        'test_api_key_limit' => 'integer',
        'live_api_key_limit' => 'integer',
        'test_key_ttl_days' => 'integer',
        'live_key_ttl_days' => 'integer',
        'request_rate_limit_max_requests' => 'integer',
        'request_rate_limit_window_seconds' => 'integer',
        'test_rate_limit_max_requests' => 'integer',
        'test_rate_limit_window_seconds' => 'integer',
        'allow_test_keys' => 'boolean',
        'allow_live_keys' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active'         => 'boolean',
        'razorpay_plan_ids' => 'array',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'archived_at' => 'datetime',
    ];

    public function features()
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }

    public function pricingHistories(): HasMany
    {
        return $this->hasMany(PlanPricingHistory::class)->latest('created_at');
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /** A plan may be hard deleted only before it has affected billing history. */
    public function canBePhysicallyDeleted(): bool
    {
        return ! $this->userSubscriptions()->exists()
            && ! $this->pricingHistories()->exists();
    }
}