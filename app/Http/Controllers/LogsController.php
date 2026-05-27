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
        $filters = $request->only(['period', 'month', 'search', 'page']);

        $paginated = $this->analytics->forLogs($user, $filters, 60);

        return Inertia::render('app/Logs', [
            'logs'            => $paginated->items(),
            'pagination'      => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ],
            'availableMonths' => $this->analytics->forUser($user)['months'],
            'selectedFilters' => $filters,
        ]);
    }
}
