<?php

namespace App\Services\Control\Overview;

class AlertsService extends BaseOverviewService
{
    public function getAlerts(): array
    {
        return $this->execute('overview:alerts', CacheTiers::REAL_TIME, function () {
            // In a real system, query Prometheus, Datadog, or local log tables.
            // For now, we will return an empty array indicating NO alerts,
            // so we can test the "Empty State" UI requirement.
            return [];
        });
    }
}
