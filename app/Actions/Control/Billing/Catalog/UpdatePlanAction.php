<?php

namespace App\Actions\Control\Billing\Catalog;

use App\DTOs\Control\Billing\Catalog\UpdatePlanData;
use App\Events\Billing\Catalog\PlanUpdated;
use App\Jobs\Control\Billing\RecalculateCatalogStatsJob;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Billing\Catalog\CatalogAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdatePlanAction
{
    public function __construct(private readonly CatalogAuditService $audit) {}

    public function execute(User $admin, SubscriptionPlan $plan, UpdatePlanData $data, ?string $ipAddress = null): SubscriptionPlan
    {
        Gate::forUser($admin)->authorize('update', $plan);

        [$updated, $changes] = DB::transaction(function () use ($admin, $plan, $data, $ipAddress): array {
            $locked = SubscriptionPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $before = $this->snapshot($locked);
            $attributes = array_filter([
                'name' => $data->name,
                'slug' => $data->slug,
                'description' => $data->description,
                'visibility' => $data->visibility?->value,
                'sort_order' => $data->sortOrder,
                'metadata' => $data->metadata,
                'notes' => $data->notes,
            ], fn ($value) => $value !== null);

            if ($data->status !== null) {
                $attributes['status'] = $data->status->value;
                $attributes['is_active'] = $data->status->value === 'active';
                $attributes['archived_at'] = $data->status->value === 'archived' ? now() : null;
            }

            // Prices are intentionally absent: existing subscribers are never
            // modified by metadata changes and pricing has its own action.
            $locked->fill($attributes);
            $changes = $locked->getDirty();
            $locked->save();

            $this->audit->record($admin, 'plan.updated', $locked, $before, $this->snapshot($locked), $ipAddress);
            Cache::tags(['catalog', 'billing'])->flush();

            return [$locked, $changes];
        });

        PlanUpdated::dispatch($updated, $changes);
        RecalculateCatalogStatsJob::dispatch();

        return $updated;
    }

    private function snapshot(SubscriptionPlan $plan): array
    {
        return $plan->only(['id', 'name', 'slug', 'description', 'status', 'visibility', 'sort_order', 'metadata', 'notes', 'archived_at', 'is_active']);
    }
}
