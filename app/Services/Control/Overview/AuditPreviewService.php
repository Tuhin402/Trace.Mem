<?php

namespace App\Services\Control\Overview;

use App\Models\AdminAuditLog;

class AuditPreviewService extends BaseOverviewService
{
    public function getAuditLogs(): array
    {
        return $this->execute('overview:audit_logs', CacheTiers::NEAR_REAL_TIME, function () {
            return AdminAuditLog::latest()->take(5)->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'actor' => $log->admin_id ?? 'System',
                    'action' => $log->action,
                    'time' => $log->created_at->diffForHumans(),
                ];
            })->toArray();
        });
    }
}
