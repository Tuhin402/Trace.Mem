<?php

use App\Jobs\DecayMemoriesSweepJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Existing: Archive stale memories ──────────────────────────────────────────
// withoutOverlapping() prevents concurrent archive runs if archiving takes
// longer than its interval (production-safety requirement, not a preference).
Schedule::command('memory:archive-stale')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->name('memory-archive-stale');

// ── New: Scheduled memory decay sweep ─────────────────────────────────────────
// Replaces the silent no-op DecayMemoryJob(0) pattern.
// Uses chunkById(200) — never loads the full memories table.
Schedule::job(new DecayMemoriesSweepJob(), 'low')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->name('memory-decay-sweep');

// ── New: Queue worker heartbeat ────────────────────────────────────────────────
// Written ONLY here — never inside jobs. The health endpoint reads this key
// to confirm the worker is alive. TTL of 120 seconds ensures the heartbeat
// expires naturally if the worker stops.
Schedule::call(function () {
    try {
        Redis::set('queue:heartbeat', now()->toIso8601String(), 'EX', 120);
    } catch (\Throwable) {
        // Silently ignore — Redis may be temporarily unavailable.
        // The health endpoint will report heartbeat_present: false.
    }
})
->everyMinute()
->name('queue-heartbeat')
->withoutOverlapping();