<?php

namespace App\Services\Control\Overview;

use App\Models\BillingTransaction;
use App\Services\Control\Billing\BillingQueryService;

class BillingOverviewService extends BaseOverviewService
{

    public function __construct(private readonly BillingQueryService $billing) {}
    public function getBilling(): array
    {
        return $this->execute('overview:billing', CacheTiers::AGGREGATED, function () {
            $stats = $this->billing->getGlobalStats();

            $transactions = BillingTransaction::latest()->take(4)->get()->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'amount' => '$' . number_format($tx->amount / 100, 2),
                    'status' => $tx->status,
                    'tenant' => $tx->tenant_scope_id ?? 'Unknown',
                    'time' => $tx->created_at->diffForHumans(),
                ];
            })->toArray();

            return [
                'active_plans' => $stats['active_plans'],
                'draft_plans' => $stats['draft_plans'],
                'archived_plans' => $stats['archived_plans'],
                'total_subscribers' => $stats['total'],
                'monthly_subscribers' => $stats['monthly'],
                'quarterly_subscribers' => $stats['quarterly'],
                'yearly_subscribers' => $stats['yearly'],
                'recent_transactions' => $transactions,
            ];
        });
    }
}
