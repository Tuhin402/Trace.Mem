<?php

namespace App\Services\Control\Billing\Catalog;

use App\Models\BillingCatalogAuditLog;
use App\Models\SubscriptionPlan;
use App\Services\Control\Billing\BillingQueryService;
use Illuminate\Support\Facades\Cache;

class CatalogQueryService
{
    public function __construct(private readonly BillingQueryService $billing) {}

    public function getPlans(array $filters = [], bool $withCounts = true): array
    {
        $status = $filters['status'] ?? 'all';
        $key = 'catalog:plans:' . md5(json_encode(['status' => $status]));

        $plans = Cache::tags(['catalog'])->remember($key, 60, function () use ($status): array {
            return SubscriptionPlan::query()
                ->when(in_array($status, ['draft', 'active', 'archived'], true), fn ($query) => $query->where('status', $status))
                ->with('features')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => $plan->toArray())
                ->all();
        });

        return collect($plans)
            ->map(fn (array $plan) => $this->planPayload($plan, $withCounts))
            ->values()
            ->all();
    }

    public function getPlan(int $planId): array
    {
        $key = "catalog:plan:{$planId}";
        $plan = Cache::tags(['catalog'])->remember($key, 60, function () use ($planId): array {
            return SubscriptionPlan::query()
                ->with(['features', 'pricingHistories.changedBy'])
                ->findOrFail($planId)
                ->toArray();
        });

        $payload = $this->planPayload($plan, true);
        $payload['pricing_histories'] = collect($plan['pricing_histories'] ?? [])
            ->map(fn (array $history) => [
                'id' => $history['id'],
                'period' => $history['period'],
                'old_amount' => $history['old_amount'],
                'new_amount' => $history['new_amount'],
                'reason' => $history['reason'],
                'changed_by' => $history['changed_by']['name'] ?? 'System',
                'created_at' => $history['created_at'],
            ])->values()->all();
        $payload['audit_logs'] = $this->getAuditLogs($planId);

        return $payload;
    }

    public function getMissingPricings(): array
    {
        return SubscriptionPlan::query()->get()->map(function (SubscriptionPlan $plan): array {
            return [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'missing_pricings' => $this->missingPricings($plan->toArray()),
            ];
        })->filter(fn (array $plan) => $plan['missing_pricings'] !== [])->values()->all();
    }

    private function getAuditLogs(int $planId): array
    {
        return BillingCatalogAuditLog::query()
            ->where('entity_type', SubscriptionPlan::class)
            ->where('entity_id', (string) $planId)
            ->with('performer:id,name')
            ->latest('created_at')
            ->get()
            ->map(fn (BillingCatalogAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'before' => $log->before,
                'after' => $log->after,
                'performed_by' => $log->performer?->name ?? 'System',
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all();
    }

    private function planPayload(array $plan, bool $withCounts): array
    {
        $counts = $withCounts
            ? $this->billing->getSubscriberCounts((int) $plan['id'])
            : ['monthly' => 0, 'quarterly' => 0, 'yearly' => 0, 'total' => 0];
        $model = SubscriptionPlan::query()->find((int) $plan['id']);

        $plan['subscriber_counts'] = $counts;
        $plan['missing_pricings'] = $this->missingPricings($plan);
        $plan['can_be_deleted'] = $model?->canBePhysicallyDeleted() ?? false;

        return $plan;
    }

    private function missingPricings(array $plan): array
    {
        return collect(['monthly', 'quarterly', 'yearly'])
            ->filter(fn (string $period) => (float) ($plan["price_{$period}"] ?? 0) === 0.0)
            ->values()
            ->all();
    }
}
