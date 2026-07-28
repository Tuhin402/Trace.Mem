<?php

namespace App\Http\Controllers\Control\Billing;

use App\Actions\Control\Billing\Catalog\UpdatePlanPricingAction;
use App\DTOs\Control\Billing\Catalog\UpdatePlanPricingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Control\Billing\UpdatePlanPricingRequest;
use App\Models\SubscriptionPlan;
use App\Services\Control\Billing\Catalog\CustomerImpactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CatalogPricingController extends Controller
{
    public function impact(Request $request, CustomerImpactService $impact): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'period' => ['required', 'in:monthly,quarterly,yearly'],
            'new_amount' => ['required', 'numeric', 'min:0'],
            'notify_subscribers' => ['boolean'],
        ]);
        $plan = SubscriptionPlan::query()->findOrFail($validated['plan_id']);
        Gate::forUser($request->user())->authorize('update', $plan);

        return response()->json($impact->calculate(new UpdatePlanPricingData(
            planId: $plan->id, period: $validated['period'], newAmount: (float) $validated['new_amount'],
            reason: null, notifySubscribers: (bool) ($validated['notify_subscribers'] ?? false),
        ))->toArray());
    }

    public function store(UpdatePlanPricingRequest $request, UpdatePlanPricingAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->execute($request->user(), new UpdatePlanPricingData(
            planId: (int) $data['plan_id'], period: $data['period'], newAmount: (float) $data['new_amount'],
            reason: $data['reason'] ?? null, notifySubscribers: (bool) ($data['notify_subscribers'] ?? false),
        ), $request->ip());

        return back()->with('success', 'Pricing updated. Existing subscribers remain grandfathered.');
    }
}
