<?php

namespace App\Actions\Control\Billing\Catalog;

use App\Events\Billing\Catalog\PlanDeleted;
use App\Jobs\Control\Billing\RecalculateCatalogStatsJob;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Billing\Catalog\CatalogAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeletePlanAction
{
    public function __construct(private readonly CatalogAuditService $audit) {}

    public function execute(User $admin, SubscriptionPlan $plan, ?string $ipAddress = null): void
    {
        Gate::forUser($admin)->authorize('forceDelete', $plan);

        if (! $plan->canBePhysicallyDeleted()) {
            throw ValidationException::withMessages(['plan' => 'Plans with subscriptions or pricing history cannot be permanently deleted.']);
        }

        [$id, $name] = DB::transaction(function () use ($admin, $plan, $ipAddress): array {
            $locked = SubscriptionPlan::query()->lockForUpdate()->findOrFail($plan->id);

            if (! $locked->canBePhysicallyDeleted()) {
                throw ValidationException::withMessages(['plan' => 'Plans with subscriptions or pricing history cannot be permanently deleted.']);
            }

            $snapshot = $locked->only(['id', 'name', 'slug', 'status']);
            $this->audit->record($admin, 'plan.deleted', $locked, $snapshot, null, $ipAddress);
            $id = $locked->id;
            $name = $locked->name;
            $locked->forceDelete();
            Cache::tags(['catalog', 'billing'])->flush();

            return [$id, $name];
        });

        PlanDeleted::dispatch($id, $name);
        RecalculateCatalogStatsJob::dispatch();
    }
}
