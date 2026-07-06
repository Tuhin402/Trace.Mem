<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Billing\FreeTrialEligibilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingController extends Controller
{
    public function __construct(
        private readonly FreeTrialEligibilityService $trialService,
    ) {}
    public function index(Request $request)
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('price_monthly', 'asc')
            ->get()
            ->map(function ($plan) {
                return [
                    'id'                               => $plan->id,
                    'slug'                             => $plan->slug,
                    'name'                             => $plan->name,
                    'description'                      => $plan->description,
                    'base_mode'                        => $plan->base_mode,
                    'memory_write_limit'               => $plan->memory_write_limit,
                    'request_limit'                    => $plan->request_limit,
                    'api_key_limit'                    => $plan->api_key_limit,
                    'request_rate_limit_max_requests'  => $plan->request_rate_limit_max_requests,
                    'request_rate_limit_window_seconds'=> $plan->request_rate_limit_window_seconds,
                    'test_rate_limit_max_requests'     => $plan->test_rate_limit_max_requests,
                    'test_rate_limit_window_seconds'   => $plan->test_rate_limit_window_seconds,
                    'allow_test_keys'                  => $plan->allow_test_keys,
                    'allow_live_keys'                  => $plan->allow_live_keys,
                    'price_monthly'                    => (float) $plan->price_monthly,
                    'price_quarterly'                  => (float) $plan->price_quarterly,
                    'price_yearly'                     => (float) $plan->price_yearly,
                    'features'                         => $plan->features->map(function ($f) {
                        return [
                            'feature_scope'  => $f->feature_scope,
                            'model_provider' => $f->model_provider,
                            'model_name'     => $f->model_name,
                            'feature_key'    => $f->feature_key,
                            'feature_value'  => $f->feature_value,
                            'is_enabled'     => $f->is_enabled,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        // ── Founding Offer unified presentation logic ──────────────────────────
        // Guests and eligible users will receive the presentation object.
        $user = $request->user();

        return Inertia::render('public/Pricing', [
            'plans'          => $plans,
            'founding_offer' => $this->trialService->getFoundingOfferPresentation($user),
        ]);
    }
}
