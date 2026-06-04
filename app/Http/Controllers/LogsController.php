<?php

namespace App\Http\Controllers;

use App\Services\Billing\ApiUsageAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogsController extends Controller
{
    public function __construct(
        private readonly ApiUsageAnalyticsService $analytics,
    ) {}

    public function index(Request $request)
    {
        $user    = $request->user();
        $filters = $request->only(['period', 'month', 'search', 'page', 'tab', 'environment', 'status', 'mode']);
        $tab     = $filters['tab'] ?? 'usage';

        $paginated = $tab === 'usage' 
            ? $this->analytics->forLogs($user, $filters, 60)
            : collect(); // Empty paginator structure if not on logs tab

        $insights = $tab === 'insights'
            ? $this->analytics->insightsForUser($user, ['period' => 'all_time'])
            : null;

        return Inertia::render('app/Logs', [
            'logs'            => $tab === 'usage' ? $paginated->items() : [],
            'pagination'      => $tab === 'usage' ? [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ] : null,
            'insights'        => $insights,
            'availableMonths' => $this->analytics->forUser($user)['months'],
            'selectedFilters' => $filters,
        ]);
    }
}
