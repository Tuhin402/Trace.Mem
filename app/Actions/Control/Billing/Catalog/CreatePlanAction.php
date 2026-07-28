<?php

namespace App\Actions\Control\Billing\Catalog;

use App\DTOs\Control\Billing\Catalog\CreatePlanData;
use App\Events\Billing\Catalog\PlanCreated;
use App\Jobs\Control\Billing\RecalculateCatalogStatsJob;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Billing\Catalog\CatalogAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatePlanAction
{
    public function __construct(private readonly CatalogAuditService $audit) {}

    public function execute(User $admin, CreatePlanData $data, ?string $ipAddress = null): SubscriptionPlan
    {
        Gate::forUser($admin)->authorize('create', SubscriptionPlan::class);

        $plan = DB::transaction(function () use ($admin, $data, $ipAddress): SubscriptionPlan {
            $plan = SubscriptionPlan::create([
                'name' => $data->name,
                'slug' => $data->slug,
                'description' => $data->description,
                'status' => $data->status->value,
                'visibility' => $data->visibility->value,
                'sort_order' => $data->sortOrder,
                'price_monthly' => $data->priceMonthly,
                'price_quarterly' => $data->priceQuarterly,
                'price_yearly' => $data->priceYearly,
                'metadata' => $data->metadata,
                'notes' => $data->notes,
                'is_active' => $data->status->value === 'active',
            ]);

            $this->audit->record($admin, 'plan.created', $plan, null, $this->snapshot($plan), $ipAddress);
            Cache::tags(['catalog', 'billing'])->flush();

            return $plan;
        });

        PlanCreated::dispatch($plan);
        RecalculateCatalogStatsJob::dispatch();

        return $plan;
    }

    private function snapshot(SubscriptionPlan $plan): array
    {
        return $plan->only(['id', 'name', 'slug', 'status', 'visibility', 'sort_order', 'price_monthly', 'price_quarterly', 'price_yearly']);
    }
}
