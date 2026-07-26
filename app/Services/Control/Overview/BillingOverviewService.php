<?php

namespace App\Services\Control\Overview;

use App\Models\BillingTransaction;

class BillingOverviewService extends BaseOverviewService
{
    public function getBilling(): array
    {
        return $this->execute('overview:billing', CacheTiers::AGGREGATED, function () {
            // Note: Since this is a massive platform, this requires a BillingTransaction model or similar
            // which we know exists from earlier checks.
            return BillingTransaction::latest()->take(4)->get()->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'amount' => '$' . number_format($tx->amount / 100, 2),
                    'status' => $tx->status,
                    'tenant' => $tx->tenant_scope_id ?? 'Unknown',
                    'time' => $tx->created_at->diffForHumans(),
                ];
            })->toArray();
        });
    }
}
