<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only history of catalog price changes. */
class PlanPricingHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'subscription_plan_id',
        'period',
        'old_amount',
        'new_amount',
        'changed_by',
        'reason',
        'razorpay_old_plan_id',
        'razorpay_new_plan_id',
    ];

    protected $casts = [
        'subscription_plan_id' => 'integer',
        'changed_by' => 'integer',
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
