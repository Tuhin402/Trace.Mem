<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Memory\MemoryLifecycleService;


class MemoryArchiveStaleMemories extends Command
{
    protected $signature = 'memory:archive-stale {--days=180} {--access-count=1} {--confidence=0.15}';
    protected $description = 'Archive stale memories that are old, weak, and rarely used';

    public function handle(MemoryLifecycleService $lifecycle): int
    {
        $archived = $lifecycle->archiveStaleMemories(
            (int) $this->option('days'),
            (int) $this->option('access-count'),
            (float) $this->option('confidence')
        );

        $this->info("Archived {$archived} stale memories.");

        return self::SUCCESS;
    }
}
