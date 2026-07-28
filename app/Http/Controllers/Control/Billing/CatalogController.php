<?php

namespace App\Http\Controllers\Control\Billing;

use App\Actions\Control\Billing\Catalog\ArchivePlanAction;
use App\Actions\Control\Billing\Catalog\CreatePlanAction;
use App\Actions\Control\Billing\Catalog\DeletePlanAction;
use App\Actions\Control\Billing\Catalog\RestorePlanAction;
use App\Actions\Control\Billing\Catalog\UpdatePlanAction;
use App\DTOs\Control\Billing\Catalog\CreatePlanData;
use App\DTOs\Control\Billing\Catalog\UpdatePlanData;
use App\Enums\CatalogPlanStatus;
use App\Enums\CatalogPlanVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Control\Billing\CreatePlanRequest;
use App\Http\Requests\Control\Billing\UpdatePlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\Control\Billing\BillingQueryService;
use App\Services\Control\Billing\Catalog\CatalogQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogQueryService $catalog,
        private readonly BillingQueryService $billing,
    ) {}

    public function index(Request $request): Response
    {
        Gate::forUser($request->user())->authorize('viewAny', SubscriptionPlan::class);
        $status = $request->string('status', 'all')->toString();

        return Inertia::render('control/billing/catalog/Index', [
            'plans' => $this->catalog->getPlans(['status' => $status]),
            'stats' => $this->billing->getGlobalStats(),
            'filters' => ['status' => $status],
            'can_manage' => $request->user()->can('create', SubscriptionPlan::class),
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $plan = SubscriptionPlan::query()->findOrFail($id);
        Gate::forUser($request->user())->authorize('view', $plan);

        return Inertia::render('control/billing/catalog/Show', [
            'plan' => $this->catalog->getPlan($id),
            'can_manage' => $request->user()->can('update', $plan),
            'is_super_admin' => $request->user()->platform_role === 'super_admin',
        ]);
    }

    public function store(CreatePlanRequest $request, CreatePlanAction $action): RedirectResponse
    {
        $data = $request->validated();
        $plan = $action->execute($request->user(), new CreatePlanData(
            name: $data['name'], slug: $data['slug'], description: $data['description'] ?? null,
            status: CatalogPlanStatus::from($data['status']), visibility: CatalogPlanVisibility::from($data['visibility']),
            sortOrder: $data['sort_order'], priceMonthly: (float) $data['price_monthly'],
            priceQuarterly: (float) $data['price_quarterly'], priceYearly: (float) $data['price_yearly'],
            metadata: $data['metadata'] ?? null, notes: $data['notes'] ?? null,
        ), $request->ip());

        return to_route('control.platform.billing.catalog.show', $plan->id)->with('success', 'Plan created.');
    }

    public function update(UpdatePlanRequest $request, int $id, UpdatePlanAction $action): RedirectResponse
    {
        $data = $request->validated();
        $plan = $action->execute($request->user(), SubscriptionPlan::query()->findOrFail($id), new UpdatePlanData(
            name: $data['name'] ?? null, slug: $data['slug'] ?? null, description: $data['description'] ?? null,
            status: isset($data['status']) ? CatalogPlanStatus::from($data['status']) : null,
            visibility: isset($data['visibility']) ? CatalogPlanVisibility::from($data['visibility']) : null,
            sortOrder: $data['sort_order'] ?? null, metadata: $data['metadata'] ?? null, notes: $data['notes'] ?? null,
        ), $request->ip());

        return to_route('control.platform.billing.catalog.show', $plan->id)->with('success', 'Plan updated.');
    }

    public function archive(Request $request, int $id, ArchivePlanAction $action): RedirectResponse
    {
        $plan = $action->execute($request->user(), SubscriptionPlan::query()->findOrFail($id), $request->ip());

        return to_route('control.platform.billing.catalog.show', $plan->id)->with('success', 'Plan archived.');
    }

    public function restore(Request $request, int $id, RestorePlanAction $action): RedirectResponse
    {
        $plan = $action->execute($request->user(), SubscriptionPlan::query()->findOrFail($id), $request->ip());

        return to_route('control.platform.billing.catalog.show', $plan->id)->with('success', 'Plan restored.');
    }

    public function destroy(Request $request, int $id, DeletePlanAction $action): RedirectResponse
    {
        $action->execute($request->user(), SubscriptionPlan::query()->findOrFail($id), $request->ip());

        return to_route('control.platform.billing.catalog.index')->with('success', 'Plan permanently deleted.');
    }
}
