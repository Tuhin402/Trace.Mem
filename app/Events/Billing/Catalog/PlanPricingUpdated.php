<?php

namespace App\Events\Billing\Catalog;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanPricingUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SubscriptionPlan $plan,
        public string $period,
        public string $oldAmount,
        public string $newAmount,
    ) {}
}
