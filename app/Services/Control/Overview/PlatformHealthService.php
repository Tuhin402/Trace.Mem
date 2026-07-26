<?php

namespace App\Services\Control\Overview;

use Illuminate\Support\Facades\DB;
use App\DTOs\Control\Overview\PlatformHealthDTO;

class PlatformHealthService extends BaseOverviewService
{
    public function getHealth(): array
    {
        return $this->execute('overview:health', CacheTiers::REAL_TIME, function () {
            // Check Database
            $dbHealthy = false;
            try {
                DB::connection()->getPdo();
                $dbHealthy = true;
            } catch (\Exception $e) {}

            return (new PlatformHealthDTO([
                [
                    'name' => 'API Gateway', 
                    'status' => 'Healthy', 
                    'color' => 'text-green-500', 
                    'bg' => 'bg-green-500/10', 
                    'border' => 'border-green-500/20'
                ],
                [
                    'name' => 'Database Engine', 
                    'status' => $dbHealthy ? 'Healthy' : 'Critical', 
                    'color' => $dbHealthy ? 'text-green-500' : 'text-destructive', 
                    'bg' => $dbHealthy ? 'bg-green-500/10' : 'bg-destructive/10', 
                    'border' => $dbHealthy ? 'border-green-500/20' : 'border-destructive/20'
                ],
                [
                    'name' => 'Memory Pipeline', 
                    'status' => 'Healthy', 
                    'color' => 'text-green-500', 
                    'bg' => 'bg-green-500/10', 
                    'border' => 'border-green-500/20'
                ],
                [
                    'name' => 'Background Workers', 
                    'status' => 'Healthy', // Would check queue worker heartbeat here
                    'color' => 'text-green-500', 
                    'bg' => 'bg-green-500/10', 
                    'border' => 'border-green-500/20'
                ],
            ]))->toArray();
        });
    }
}
