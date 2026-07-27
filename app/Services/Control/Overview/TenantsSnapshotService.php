<?php

namespace App\Services\Control\Overview;

use App\Models\Tenant;

class TenantsSnapshotService extends BaseOverviewService
{
    public function getTenants(): array
    {
        return $this->execute('overview:tenants_snapshot', CacheTiers::NEAR_REAL_TIME, function () {
            return Tenant::latest()->take(4)->get()->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'slug' => $tenant->slug,
                    'name' => $tenant->name,
                    'plan' => $tenant->plan ?? 'Legacy',
                    'status' => $tenant->status,
                    'time' => $tenant->created_at->diffForHumans(),
                ];
            })->toArray();
        });
    }
}
