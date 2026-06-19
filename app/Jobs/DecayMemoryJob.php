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
 * DecayMemoryJob — queue: 'low'
 *
 * Applies decay to a single specific memory by ID. Intended for explicit
 * lifecycle hooks in the future (e.g., when a memory is explicitly marked
 * as stale by a user action).
 *
 * NOT scheduler-dispatched. The scheduled batch decay is handled by
 * DecayMemoriesSweepJob, which avoids the DecayMemoryJob(0) no-op bug.
 *
 * This job is safe to dispatch with any valid memory ID and any decay amount.
 */
class DecayMemoryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $memoryId,
        public readonly float $decayAmount = 0.05,
    ) {
        $this->onQueue('low');
    }

    public function handle(MemoryLifecycleService $lifecycle): void
    {
        $memory = Memory::find($this->memoryId);

        if (! $memory) {
            Log::warning('DecayMemoryJob: memory not found', [
                'memory_id' => $this->memoryId,
            ]);
            return;
        }

        // Skip archived memories
        if ($memory->status === 'archived' || $memory->archived_at !== null) {
            return;
        }

        $newDecay = max(0.0, round((float) $memory->decay_score - $this->decayAmount, 4));

        $memory->forceFill([
            'decay_score' => $newDecay,
            'status'      => $lifecycle->classifyStatus($memory),
        ])->saveQuietly();

        Log::info('DecayMemoryJob: applied decay', [
            'memory_id'    => $this->memoryId,
            'decay_amount' => $this->decayAmount,
            'new_score'    => $newDecay,
            'new_status'   => $memory->status,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DecayMemoryJob: job failed', [
            'memory_id' => $this->memoryId,
            'error'     => $exception->getMessage(),
        ]);
    }
}
