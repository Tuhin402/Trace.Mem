<?php

namespace App\Jobs\Control\Billing;

use App\Models\PlanPricingHistory;
use App\Models\SubscriptionPlan;
use App\Services\Control\Billing\Catalog\CatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncRazorpayPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $planId,
        public readonly string $period,
        public readonly float $newAmount,
        public readonly ?int $pricingHistoryId = null,
    ) {
        $this->onQueue('billing');
    }

    public function handle(CatalogSyncService $catalogSync): void
    {
        // A changed price always creates a fresh Razorpay plan. Old IDs remain
        // referenced by existing subscriptions, which preserves grandfathering.
        $razorpayPlanId = $catalogSync->syncPricingToRazorpay($this->planId, $this->period, $this->newAmount);

        DB::transaction(function () use ($razorpayPlanId): void {
            $plan = SubscriptionPlan::query()->lockForUpdate()->findOrFail($this->planId);
            $ids = (array) $plan->razorpay_plan_ids;
            $ids[$this->period] = $razorpayPlanId;
            $plan->forceFill(['razorpay_plan_ids' => $ids])->save();

            if ($this->pricingHistoryId !== null) {
                PlanPricingHistory::query()->whereKey($this->pricingHistoryId)->update([
                    'razorpay_new_plan_id' => $razorpayPlanId,
                ]);
            }
        });
    }
}
