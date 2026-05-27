<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlanFeature extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'feature_scope',
        'model_provider',
        'model_name',
        'feature_key',
        'feature_value',
        'is_enabled',
    ];

    protected $casts = [
        'subscription_plan_id' => 'integer',
        'feature_scope' => 'string',
        'feature_value' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}