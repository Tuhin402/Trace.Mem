<?php

namespace App\Services\Billing;

use App\Models\ApiUsageLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApiUsageAnalyticsService
{
    // ─── Public API ─────────────────────────────────────────────────────

    /**
     * Returns summary stats + recent activity + available months.
     * Used by the Dashboard controller.
     */
    public function forUser(User $user, array $filters = []): array
    {
        $keyIds = $user->apiKeys()->pluck('id');

        if ($keyIds->isEmpty()) {
            return $this->emptyResponse();
        }

        $base     = ApiUsageLog::query()->whereIn('api_key_id', $keyIds);
        $filtered = $this->applyFilters(clone $base, $filters);

        return [
            'summary' => [
                'total_requests'      => (clone $filtered)->count(),
                'requests_24h'        => (clone $base)->where('requested_at', '>=', now()->subDay())->count(),
                'successful_requests' => (clone $filtered)->whereBetween('status_code', [200, 299])->count(),
                'client_errors'       => (clone $filtered)->whereBetween('status_code', [400, 499])->count(),
                'server_errors'       => (clone $filtered)->where('status_code', '>=', 500)->count(),
                'slow_requests'       => (clone $filtered)->where('latency_ms', '>=', 2000)->count(),
                'avg_latency_ms'      => (int) round((clone $filtered)->avg('latency_ms') ?: 0),
            ],
            'recent'  => $this->recentLogs($filtered, 20),
            'months'  => $this->availableMonths($base),
        ];
    }

    /**
     * Returns logs for the dedicated Logs page with pagination.
     * Supports period / month / search filters.
     */
    public function forLogs(User $user, array $filters = [], int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $keyIds = $user->apiKeys()->pluck('id');

        $query = ApiUsageLog::query()
            ->whereIn('api_key_id', $keyIds)
            ->with('apiKey:id,name,environment,mode');

        $query = $this->applyFilters($query, $filters);

        // Search
        if (! empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('endpoint', 'like', "%{$q}%")
                    ->orWhere('request_id', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('request_host', 'like', "%{$q}%")
                    ->orWhere('request_origin', 'like', "%{$q}%")
                    ->orWhere('status_code', 'like', "%{$q}%");
            });
        }

        return $query
            ->select([
                'id', 'api_key_id', 'endpoint', 'method', 'status_code',
                'latency_ms', 'ip_address', 'request_host', 'request_origin',
                'is_sandbox', 'is_localhost', 'request_id', 'requested_at',
            ])
            ->latest('requested_at')
            ->paginate($perPage);
    }

    // ─── Private helpers ─────────────────────────────────────────────────

    private function recentLogs($query, int $limit): Collection
    {
        return (clone $query)
            ->with('apiKey:id,name,environment,mode')
            ->select([
                'id', 'api_key_id', 'endpoint', 'method', 'status_code',
                'latency_ms', 'ip_address', 'request_host', 'request_origin',
                'is_sandbox', 'is_localhost', 'request_id', 'requested_at',
            ])
            ->latest('requested_at')
            ->limit($limit)
            ->get();
    }

    private function applyFilters($query, array $filters)
    {
        $period = $filters['period'] ?? 'all_time';
        $month  = $filters['month'] ?? null;

        if ($month) {
            [$start, $end] = $this->monthRange($month);
            return $query->whereBetween('requested_at', [$start, $end]);
        }

        return match ($period) {
            'this_month'   => $query->whereBetween('requested_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'last_month'   => $query->whereBetween('requested_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ]),
            'year_to_date' => $query->whereBetween('requested_at', [now()->startOfYear(), now()]),
            default        => $query,  // all_time — no filter
        };
    }

    private function monthRange(string $month): array
    {
        $date = preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::parse("first day of {$month} " . now()->year)->startOfMonth();

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    /**
     * Returns a Collection of "YYYY-MM" strings for months that have log records.
     * Uses DB-driver–agnostic raw SQL to avoid SQLite-only strftime.
     */
    private function availableMonths($base): Collection
    {
        $driver = DB::getDriverName();

        $expression = match ($driver) {
            'mysql', 'mariadb' => "DATE_FORMAT(requested_at, '%Y-%m') as month",
            'pgsql'            => "TO_CHAR(requested_at, 'YYYY-MM') as month",
            default            => "strftime('%Y-%m', requested_at) as month",  // SQLite
        };

        return (clone $base)
            ->selectRaw($expression)
            ->groupBy('month')
            ->orderByDesc('month')
            ->pluck('month');
    }

    private function emptyResponse(): array
    {
        return [
            'summary' => [
                'total_requests'      => 0,
                'requests_24h'        => 0,
                'successful_requests' => 0,
                'client_errors'       => 0,
                'server_errors'       => 0,
                'slow_requests'       => 0,
                'avg_latency_ms'      => 0,
            ],
            'recent'  => collect(),
            'months'  => collect(),
        ];
    }
}