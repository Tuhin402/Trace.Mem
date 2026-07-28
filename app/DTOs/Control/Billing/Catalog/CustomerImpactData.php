<?php

namespace App\DTOs\Control\Billing\Catalog;

readonly class CustomerImpactData
{
    public function __construct(
        public int $affectedCount,
        public string $period,
        public string $currentPrice,
        public string $newPrice,
        public bool $willNotify,
        public string $grandfatheringNote,
    ) {}

    public function toArray(): array
    {
        return [
            'affected_count' => $this->affectedCount,
            'period' => $this->period,
            'current_price' => $this->currentPrice,
            'new_price' => $this->newPrice,
            'will_notify' => $this->willNotify,
            'grandfathering_note' => $this->grandfatheringNote,
        ];
    }
}
