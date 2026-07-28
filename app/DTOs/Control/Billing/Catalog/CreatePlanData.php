<?php

namespace App\DTOs\Control\Billing\Catalog;

use App\Enums\CatalogPlanStatus;
use App\Enums\CatalogPlanVisibility;

readonly class CreatePlanData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public CatalogPlanStatus $status,
        public CatalogPlanVisibility $visibility,
        public int $sortOrder,
        public float $priceMonthly,
        public float $priceQuarterly,
        public float $priceYearly,
        public ?array $metadata,
        public ?string $notes,
    ) {}
}
