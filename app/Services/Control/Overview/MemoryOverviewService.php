<?php

namespace App\Services\Control\Overview;

use App\Models\Memory;
use Illuminate\Support\Facades\DB;

class MemoryOverviewService extends BaseOverviewService
{
    public function getMemoryStats(): array
    {
        return $this->execute('overview:memory_stats', CacheTiers::AGGREGATED, function () {
            // Requirement: Count() aggregate in SQL, not hydration
            $totalMemories = Memory::count();
            $todayMemories = Memory::whereDate('created_at', today())->count();
            
            // Dummy chart data for now, ideally queried grouped by date
            $chartData = [
                ['name' => 'Mon', 'memories' => 1200],
                ['name' => 'Tue', 'memories' => 1500],
                ['name' => 'Wed', 'memories' => 1800],
                ['name' => 'Thu', 'memories' => 1400],
                ['name' => 'Fri', 'memories' => 2200],
                ['name' => 'Sat', 'memories' => 2600],
                ['name' => 'Sun', 'memories' => 3100],
            ];

            return [
                'total' => $totalMemories,
                'today' => $todayMemories,
                'chart' => $chartData
            ];
        });
    }
}
