<?php

namespace App\Services\Control\Billing;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Single source of truth for catalog subscriber counts. */
class BillingQueryService
{
    public function getSubscriberCounts(int $planId): array
    {
        return Cache::tags(['catalog', "catalog:stats:{$planId}"])->remember(
            "catalog:stats:{$planId}",
            60,
            function () use ($planId): array {
                $counts = UserSubscription::query()
                    ->where('subscription_plan_id', $planId)
                    ->where('is_active', true)
                    ->whereNull('cancelled_at')
                    ->select('billing_cycle', DB::raw('count(*) as count'))
                    ->groupBy('billing_cycle')
                    ->pluck('count', 'billing_cycle')
                    ->map(fn ($count) => (int) $count)
                    ->all();

                return [
                    'monthly' => $counts['monthly'] ?? 0,
                    'quarterly' => $counts['quarterly'] ?? 0,
                    'yearly' => $counts['yearly'] ?? 0,
                    'total' => array_sum($counts),
                ];
            },
        );
    }

    public function getTotalActiveSubscribers(): int
    {
        return $this->getGlobalStats()['total'];
    }

    public function getGlobalStats(): array
    {
        return Cache::tags(['catalog', 'billing'])->remember('catalog:stats:global', 60, function (): array {
            $subscribers = UserSubscription::query()
                ->where('is_active', true)
                ->whereNull('cancelled_at')
                ->select('billing_cycle', DB::raw('count(*) as count'))
                ->groupBy('billing_cycle')
                ->pluck('count', 'billing_cycle')
                ->map(fn ($count) => (int) $count)
                ->all();

            $plans = SubscriptionPlan::query()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->map(fn ($count) => (int) $count)
                ->all();

            return [
                'total' => array_sum($subscribers),
                'monthly' => $subscribers['monthly'] ?? 0,
                'quarterly' => $subscribers['quarterly'] ?? 0,
                'yearly' => $subscribers['yearly'] ?? 0,
                'active_plans' => $plans['active'] ?? 0,
                'draft_plans' => $plans['draft'] ?? 0,
                'archived_plans' => $plans['archived'] ?? 0,
            ];
        });
    }
}
