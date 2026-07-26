<?php

namespace App\Services\Control\Overview;

use App\Models\Tenant;
use App\Models\Team;
use App\Models\User;
use App\Models\Memory;
use App\DTOs\Control\Overview\MetricsDTO;

class MetricsService extends BaseOverviewService
{
    public function getMetrics(): array
    {
        return $this->execute('overview:metrics', CacheTiers::AGGREGATED, function () {
            // Must use aggregate COUNT() per requirements
            $totalTenants = Tenant::count();
            $activeWorkspaces = Team::where('status', 'active')->count();
            $platformUsers = User::count();
            $apiRequests24h = 0; // Requires ApiUsageLog table integration

            return (new MetricsDTO(
                $totalTenants,
                $activeWorkspaces,
                $platformUsers,
                $apiRequests24h
            ))->toArray();
        });
    }
}
