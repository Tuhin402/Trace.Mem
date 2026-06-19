<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'user_subscription_id',
        'provider',
        'provider_checkout_session_id',
        'provider_payment_intent_id',
        'provider_subscription_id',
        'provider_invoice_id',
        'billing_cycle',
        'currency',
        'amount_total',
        'status',
        'raw_payload',
        'metadata',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'metadata'    => 'array',
        'amount_total' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }
}
