<?php

namespace App\Services\Control\Overview;

use App\Models\Team;

class WorkspacesSnapshotService extends BaseOverviewService
{
    public function getWorkspaces(): array
    {
        return $this->execute('overview:workspaces_snapshot', CacheTiers::NEAR_REAL_TIME, function () {
            return Team::latest()->take(4)->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'slug' => $team->slug,
                    'tenant_slug' => $team->tenant?->slug ?? 'legacy',
                    'name' => $team->name,
                    'status' => $team->status,
                    'environment' => $team->environment ?? 'Production',
                    'time' => $team->created_at->diffForHumans(),
                ];
            })->toArray();
        });
    }
}
