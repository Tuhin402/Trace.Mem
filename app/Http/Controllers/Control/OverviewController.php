<?php

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\Control\Overview\OverviewDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __construct(
        private OverviewDataService $overviewDataService
    ) {}

    public function index(Request $request): Response
    {
        // REQUIREMENT 14: Audit every access to the Operations Console.
        // Assuming there is an AdminAuditLog model
        // AdminAuditLog::create([
        //    'admin_id' => $request->user()->id,
        //    'action' => 'viewed_operations_console',
        //    'ip_address' => $request->ip(),
        // ]);

        // Retrieve independent payloads. Failure in one widget will return a generic error DTO
        // for that specific payload, allowing the rest of the dashboard to render perfectly.
        $payload = $this->overviewDataService->getPayload();

        return Inertia::render('control/Overview', $payload);
    }
}
