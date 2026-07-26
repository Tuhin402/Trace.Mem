<?php

namespace App\Services\Control\Overview;

use Illuminate\Support\Facades\DB;

class JobsOverviewService extends BaseOverviewService
{
    public function getJobs(): array
    {
        return $this->execute('overview:jobs', CacheTiers::REAL_TIME, function () {
            // Check jobs table and failed_jobs table
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            
            return [
                'pending' => $pending,
                'failed' => $failed,
                'status' => $failed > 0 ? 'Degraded' : 'Healthy',
                'longestRunning' => '0m', // Requires horizon or complex query
            ];
        });
    }
}
