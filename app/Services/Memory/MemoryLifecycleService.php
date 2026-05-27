<?php

namespace App\Services\Memory;

use App\Models\Memory;
use Illuminate\Support\Carbon;

class MemoryLifecycleService
{
    public function reinforceOnRecall(Memory $memory): Memory
    {
        $now = now();

        $boost = $this->reinforcementBoost($memory);

        $memory->forceFill([
            'access_count' => ((int) $memory->access_count) + 1,
            'last_accessed_at' => $now,
            'last_reinforced_at' => $now,
            'importance' => $this->toDecimal4(
                min(1.0, (float) $memory->importance + $boost['importance'])
            ),
            'confidence' => $this->toDecimal4(
                min(1.0, (float) $memory->confidence + $boost['confidence'])
            ),
            'decay_score' => $this->toDecimal4(
                min(1.0, max(0.1, (float) $memory->decay_score + $boost['decay']))
            ),
            'status' => $this->classifyStatus($memory),
        ])->save();

        $memory->status = $this->classifyStatus($memory);
        $memory->saveQuietly();

        return $memory->fresh();
    }

    public function archiveStaleMemories(
        int $inactiveDays = 180,
        int $maxAccessCount = 1,
        float $maxConfidence = 0.35
    ): int {
        $cutoff = now()->subDays($inactiveDays);
        $archived = 0;
        $now = now();

        Memory::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereIn('status', ['active', 'stale']);
            })
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_accessed_at')
                    ->orWhere('last_accessed_at', '<', $cutoff);
            })
            ->where('access_count', '<=', $maxAccessCount)
            ->where('confidence', '<=', $maxConfidence)
            ->orderBy('id')
            ->chunkById(100, function ($memories) use (&$archived, $now) {
                foreach ($memories as $memory) {
                    $memory->forceFill([
                        'status' => 'archived',
                        'archived_at' => $now,
                        'decay_score' => '0.1000',
                    ])->save();

                    $archived++;
                }
            });

        return $archived;
    }

    public function classifyStatus(Memory $memory): string
    {
        if ($memory->status === 'archived' || $memory->archived_at !== null) {
            return 'archived';
        }

        $anchor = $memory->last_accessed_at ?: $memory->updated_at ?: $memory->created_at;
        $days = $anchor ? now()->diffInDays(Carbon::parse($anchor)) : 999;

        if ($days >= 90 || ((float) $memory->confidence < 0.45 && (int) $memory->access_count < 2)) {
            return 'stale';
        }

        return 'active';
    }

    private function reinforcementBoost(Memory $memory): array
    {
        $accessCount = (int) $memory->access_count;

        return [
            'importance' => min(0.05, 0.01 + ($accessCount * 0.002)),
            'confidence' => min(0.08, 0.02 + ($accessCount * 0.003)),
            'decay' => min(0.05, 0.01 + ($accessCount * 0.001)),
        ];
    }

    private function toDecimal4(float $value): string
    {
        return number_format($value, 4, '.', '');
    }
}