<?php

namespace App\Actions\Control\Billing\Catalog;

use App\Events\Billing\Catalog\PlanRestored;
use App\Jobs\Control\Billing\RecalculateCatalogStatsJob;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Billing\Catalog\CatalogAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestorePlanAction
{
    public function __construct(private readonly CatalogAuditService $audit) {}

    public function execute(User $admin, SubscriptionPlan $plan, ?string $ipAddress = null): SubscriptionPlan
    {
        Gate::forUser($admin)->authorize('restore', $plan);

        $updated = DB::transaction(function () use ($admin, $plan, $ipAddress): SubscriptionPlan {
            $locked = SubscriptionPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $before = $locked->only(['status', 'archived_at', 'is_active']);
            $locked->update(['status' => 'active', 'archived_at' => null, 'is_active' => true]);
            $this->audit->record($admin, 'plan.restored', $locked, $before, $locked->only(['status', 'archived_at', 'is_active']), $ipAddress);
            Cache::tags(['catalog', 'billing'])->flush();

            return $locked;
        });

        PlanRestored::dispatch($updated);
        RecalculateCatalogStatsJob::dispatch();

        return $updated;
    }
}
