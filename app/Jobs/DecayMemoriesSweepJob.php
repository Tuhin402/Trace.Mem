<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Services\Memory\MemoryLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DecayMemoriesSweepJob — queue: 'low'
 *
 * Scheduler-triggered batch decay job. Replaces the broken DecayMemoryJob(0)
 * pattern (which was a silent no-op — Memory::find(0) returns null).
 *
 * Queries all memories that need decay applied and processes them in safe
 * 200-record chunks using chunkById to avoid loading the full table.
 *
 * Eligible memories:
 *   - status in ['active', 'stale']
 *   - last_accessed_at is not null
 *   - last_accessed_at older than 30 days
 *   - decay_score > 0.05 (still has room to decay)
 *
 * Scheduled in routes/console.php at 03:00 daily with withoutOverlapping().
 */
class DecayMemoriesSweepJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(MemoryLifecycleService $lifecycle): void
    {
        $processed = 0;

        Memory::query()
            ->whereIn('status', ['active', 'stale'])
            ->whereNotNull('last_accessed_at')
            ->where('last_accessed_at', '<', now()->subDays(30))
            ->where('decay_score', '>', 0.05)
            ->orderBy('id')
            ->chunkById(200, function (Collection $chunk) use ($lifecycle, &$processed) {
                foreach ($chunk as $memory) {
                    $newDecay = max(0.0, round((float) $memory->decay_score - 0.05, 4));

                    $memory->forceFill([
                        'decay_score' => $newDecay,
                        'status'      => $lifecycle->classifyStatus($memory),
                    ])->saveQuietly();

                    $processed++;
                }
            });

        Log::info('DecayMemoriesSweepJob: completed', [
            'processed' => $processed,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DecayMemoriesSweepJob: job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
