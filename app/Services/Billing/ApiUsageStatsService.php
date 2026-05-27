<?php

namespace App\Services\Billing;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\User;
use Illuminate\Support\Collection;

class ApiUsageStatsService
{
    public function forUser(User $user): array
    {
        $keyIds = $user->apiKeys()->pluck('id');

        if ($keyIds->isEmpty()) {
            return $this->emptyStats();
        }

        $base = ApiUsageLog::query()->whereIn('api_key_id', $keyIds);

        return [
            'total_requests' => (clone $base)->count(),
            'requests_24h' => (clone $base)->where('requested_at', '>=', now()->subDay())->count(),
            'successful_requests' => (clone $base)->whereBetween('status_code', [200, 299])->count(),
            'client_errors' => (clone $base)->whereBetween('status_code', [400, 499])->count(),
            'server_errors' => (clone $base)->where('status_code', '>=', 500)->count(),
            'slow_requests' => (clone $base)->where('latency_ms', '>=', 2000)->count(),
            'avg_latency_ms' => (int) round((clone $base)->avg('latency_ms') ?: 0),
            'top_endpoint' => (clone $base)
                ->selectRaw('endpoint, count(*) as total')
                ->groupBy('endpoint')
                ->orderByDesc('total')
                ->value('endpoint'),
            'recent' => (clone $base)
                ->with('apiKey:id,name,environment,mode')
                ->latest('requested_at')
                ->limit(8)
                ->get(),
        ];
    }

    public function forApiKey(ApiKey $apiKey): array
    {
        $base = ApiUsageLog::query()->where('api_key_id', $apiKey->id);

        return [
            'total_requests' => (clone $base)->count(),
            'requests_24h' => (clone $base)->where('requested_at', '>=', now()->subDay())->count(),
            'successful_requests' => (clone $base)->whereBetween('status_code', [200, 299])->count(),
            'client_errors' => (clone $base)->whereBetween('status_code', [400, 499])->count(),
            'server_errors' => (clone $base)->where('status_code', '>=', 500)->count(),
            'slow_requests' => (clone $base)->where('latency_ms', '>=', 2000)->count(),
            'avg_latency_ms' => (int) round((clone $base)->avg('latency_ms') ?: 0),
            'recent' => (clone $base)
                ->latest('requested_at')
                ->limit(8)
                ->get(),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_requests' => 0,
            'requests_24h' => 0,
            'successful_requests' => 0,
            'client_errors' => 0,
            'server_errors' => 0,
            'slow_requests' => 0,
            'avg_latency_ms' => 0,
            'top_endpoint' => null,
            'recent' => collect(),
        ];
    }
}