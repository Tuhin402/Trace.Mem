<?php

namespace App\Services\Control\Billing\Catalog;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** The sole catalog path that communicates with the Razorpay Plans API. */
class CatalogSyncService
{
    public function syncPricingToRazorpay(int $planId, string $period, float $amount): ?string
    {
        $plan = SubscriptionPlan::query()->findOrFail($planId);
        [$razorpayPeriod, $interval] = match ($period) {
            'monthly' => ['monthly', 1],
            'quarterly' => ['monthly', 3],
            'yearly' => ['yearly', 1],
            default => throw new RuntimeException('Unsupported billing period.'),
        };

        $response = Http::withBasicAuth(
            (string) config('services.razorpay.key_id'),
            (string) config('services.razorpay.key_secret'),
        )->acceptJson()->post('https://api.razorpay.com/v1/plans', [
            'period' => $razorpayPeriod,
            'interval' => $interval,
            'item' => [
                'name' => "TraceMem {$plan->name} ({$period})",
                'amount' => (int) round($amount * 100),
                'currency' => 'INR',
            ],
            'notes' => [
                'subscription_plan_id' => (string) $plan->id,
                'billing_period' => $period,
            ],
        ])->throw();

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Razorpay did not return a plan ID.');
        }

        return $id;
    }
}
