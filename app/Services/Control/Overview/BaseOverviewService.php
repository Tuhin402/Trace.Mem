<?php

namespace App\Services\Control\Overview;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseOverviewService
{
    /**
     * Wrap execution with standard error handling and optional caching.
     */
    protected function execute(string $cacheKey, CacheTiers $tier, callable $callback)
    {
        try {
            if ($tier === CacheTiers::REAL_TIME) {
                return $callback();
            }

            return Cache::remember($cacheKey, $tier->value, function () use ($callback) {
                return $callback();
            });
        } catch (\Throwable $e) {
            Log::error("Overview Service Error [{$cacheKey}]: " . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return [
                'error' => 'Widget Unavailable',
                'message' => 'Failed to load data.'
            ];
        }
    }
}
