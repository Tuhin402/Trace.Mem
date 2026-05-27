<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'is_active' => 'boolean',
    ];

    public function features()
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }
}