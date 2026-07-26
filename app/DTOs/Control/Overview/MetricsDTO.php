<?php

namespace App\DTOs\Control\Overview;

use Illuminate\Contracts\Support\Arrayable;

class MetricsDTO implements Arrayable
{
    public function __construct(
        public readonly int $totalTenants,
        public readonly int $activeWorkspaces,
        public readonly int $platformUsers,
        public readonly int $apiRequests24h
    ) {}

    public function toArray(): array
    {
        return [
            'totalTenants' => $this->totalTenants,
            'activeWorkspaces' => $this->activeWorkspaces,
            'platformUsers' => $this->platformUsers,
            'apiRequests24h' => $this->apiRequests24h,
        ];
    }
}
