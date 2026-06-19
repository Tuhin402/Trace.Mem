<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Services\Memory\MemoryLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ReinforceMemoriesJob — queue: 'low'
 *
 * Batches the reinforcement of memories that were recalled in a single
 * recall request. Instead of N synchronous DB writes per recall (one per
 * memory), MemoryService::recall() dispatches exactly one job with all
 * returned memory IDs.
 *
 * The recall endpoint returns the pre-reinforcement state of each memory
 * (same response shape as before). Reinforcement happens asynchronously
 * after the response is sent.
 *
 * Safety: silently skips missing or archived IDs. Never fails the whole
 * batch if one memory is missing.
 */
class ReinforceMemoriesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $memoryIds,
    ) {
        $this->onQueue('low');
    }

    public function handle(MemoryLifecycleService $lifecycle): void
    {
        if (empty($this->memoryIds)) {
            return;
        }

        $memories = Memory::whereIn('id', $this->memoryIds)->get();

        foreach ($memories as $memory) {
            // Skip archived memories — reinforcement is not applicable
            if ($memory->status === 'archived' || $memory->archived_at !== null) {
                continue;
            }

            try {
                $lifecycle->reinforceOnRecall($memory);
            } catch (\Throwable $e) {
                Log::warning('ReinforceMemoriesJob: failed to reinforce memory', [
                    'memory_id' => $memory->id,
                    'error'     => $e->getMessage(),
                ]);
                // Continue with remaining memories — don't abort the whole batch
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ReinforceMemoriesJob: job failed', [
            'memory_ids' => $this->memoryIds,
            'error'      => $exception->getMessage(),
        ]);
    }
}
