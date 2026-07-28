<?php

namespace App\DTOs\Control\Billing\Catalog;

readonly class UpdatePlanPricingData
{
    public function __construct(
        public int $planId,
        public string $period,
        public float $newAmount,
        public ?string $reason,
        public bool $notifySubscribers,
    ) {}
}
