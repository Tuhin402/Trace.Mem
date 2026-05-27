<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

class BillingCatalogService
{
    public function activePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('price_monthly')
            ->get();
    }
}