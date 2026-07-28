<?php

namespace App\DTOs\Control\Billing\Catalog;

use App\Enums\CatalogPlanStatus;
use App\Enums\CatalogPlanVisibility;

readonly class UpdatePlanData
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?CatalogPlanStatus $status = null,
        public ?CatalogPlanVisibility $visibility = null,
        public ?int $sortOrder = null,
        public ?array $metadata = null,
        public ?string $notes = null,
    ) {}
}
