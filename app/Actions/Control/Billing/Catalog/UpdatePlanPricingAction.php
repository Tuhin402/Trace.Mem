<?php

namespace App\Actions\Control\Billing\Catalog;

use App\DTOs\Control\Billing\Catalog\UpdatePlanPricingData;
use App\Events\Billing\Catalog\PlanPricingUpdated;
use App\Jobs\Control\Billing\RecalculateCatalogStatsJob;
use App\Jobs\Control\Billing\SendPricingChangeNotificationJob;
use App\Jobs\Control\Billing\SyncRazorpayPlanJob;
use App\Models\PlanPricingHistory;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Billing\Catalog\CatalogAuditService;
use App\Services\Control\Billing\Catalog\CustomerImpactService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdatePlanPricingAction
{
    public function __construct(
        private readonly CustomerImpactService $impact,
        private readonly CatalogAuditService $audit,
    ) {}

    public function execute(User $admin, UpdatePlanPricingData $data, ?string $ipAddress = null): SubscriptionPlan
    {
        $plan = SubscriptionPlan::query()->findOrFail($data->planId);
        Gate::forUser($admin)->authorize('update', $plan);

        // This is intentionally evaluated before any write and never cached.
        $customerImpact = $this->impact->calculate($data);
        $subscriberIds = $data->notifySubscribers
            ? $this->impact->affectedSubscriberIds($data->planId, $data->period)
            : [];

        [$updated, $history] = DB::transaction(function () use ($admin, $data, $ipAddress, $customerImpact): array {
            $locked = SubscriptionPlan::query()->lockForUpdate()->findOrFail($data->planId);
            $column = "price_{$data->period}";
            $oldAmount = number_format((float) $locked->{$column}, 2, '.', '');
            $newAmount = number_format($data->newAmount, 2, '.', '');

            if ($oldAmount === $newAmount) {
                throw ValidationException::withMessages(['new_amount' => 'The new price must be different from the current price.']);
            }

            $oldPlanId = (array) $locked->razorpay_plan_ids;
            $locked->update([$column => $newAmount]);
            $history = PlanPricingHistory::create([
                'subscription_plan_id' => $locked->id,
                'period' => $data->period,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'changed_by' => $admin->id,
                'reason' => $data->reason,
                'razorpay_old_plan_id' => $oldPlanId[$data->period] ?? null,
            ]);

            $this->audit->record($admin, 'plan.pricing.updated', $locked, [
                $column => $oldAmount,
                'razorpay_plan_id' => $oldPlanId[$data->period] ?? null,
            ], [
                $column => $newAmount,
                'period' => $data->period,
                'reason' => $data->reason,
                'affected_subscribers' => $customerImpact->affectedCount,
            ], $ipAddress);
            Cache::tags(['catalog', 'billing'])->flush();

            return [$locked, $history];
        });

        $oldAmount = $history->old_amount;
        $newAmount = $history->new_amount;
        PlanPricingUpdated::dispatch($updated, $data->period, $oldAmount, $newAmount);
        SyncRazorpayPlanJob::dispatch($updated->id, $data->period, (float) $newAmount, $history->id);
        foreach ($subscriberIds as $userId) {
            SendPricingChangeNotificationJob::dispatch($userId, $updated->id, $data->period, $oldAmount, $newAmount, $admin->id);
        }
        RecalculateCatalogStatsJob::dispatch();

        return $updated;
    }
}
