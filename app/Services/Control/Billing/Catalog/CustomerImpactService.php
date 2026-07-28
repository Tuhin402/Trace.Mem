<?php

namespace App\Services\Control\Billing\Catalog;

use App\DTOs\Control\Billing\Catalog\CustomerImpactData;
use App\DTOs\Control\Billing\Catalog\UpdatePlanPricingData;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;

class CustomerImpactService
{
    /** Always fresh: this is called before a price mutation is committed. */
    public function calculate(UpdatePlanPricingData $data): CustomerImpactData
    {
        $plan = SubscriptionPlan::query()->findOrFail($data->planId);
        $column = "price_{$data->period}";

        return new CustomerImpactData(
            affectedCount: $this->affectedSubscribers($data->planId, $data->period)->count(),
            period: $data->period,
            currentPrice: number_format((float) $plan->{$column}, 2, '.', ''),
            newPrice: number_format($data->newAmount, 2, '.', ''),
            willNotify: $data->notifySubscribers,
            grandfatheringNote: 'Existing subscribers are grandfathered and keep their current price until a future migration is explicitly run.',
        );
    }

    public function affectedSubscriberIds(int $planId, string $period): array
    {
        return $this->affectedSubscribers($planId, $period)->pluck('user_id')->all();
    }

    private function affectedSubscribers(int $planId, string $period)
    {
        return UserSubscription::query()
            ->where('subscription_plan_id', $planId)
            ->where('billing_cycle', $period)
            ->where('is_active', true)
            ->whereNull('cancelled_at');
    }
}
