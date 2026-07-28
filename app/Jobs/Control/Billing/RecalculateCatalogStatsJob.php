<?php

namespace App\Jobs\Control\Billing;

use App\Models\SubscriptionPlan;
use App\Services\Control\Billing\BillingQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecalculateCatalogStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('billing');
    }

    public function handle(BillingQueryService $billing): void
    {
        Cache::tags(['catalog', 'billing'])->flush();

        SubscriptionPlan::query()->pluck('id')->each(function (int $planId) use ($billing): void {
            $billing->getSubscriberCounts($planId);
        });
        $billing->getGlobalStats();
    }
}
