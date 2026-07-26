<?php

namespace App\Services\Control\Overview;

use App\Models\WorkspaceAuditLog;
use App\Models\AdminAuditLog;

class SystemActivityService extends BaseOverviewService
{
    public function getActivity(): array
    {
        return $this->execute('overview:system_activity', CacheTiers::NEAR_REAL_TIME, function () {
            // For the timeline, we just want a unified feed.
            // Let's use AdminAuditLog for now, if empty, return empty.
            $logs = AdminAuditLog::latest()->take(10)->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'actor' => $log->admin_id ?? 'System',
                    'action' => $log->action,
                    'description' => $log->description ?? $log->action,
                    'time' => $log->created_at->diffForHumans(),
                ];
            })->toArray();

            return $logs;
        });
    }
}
