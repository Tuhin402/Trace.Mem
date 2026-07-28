<?php

namespace App\Events\Billing\Catalog;

use Illuminate\Foundation\Events\Dispatchable;

class PlanDeleted
{
    use Dispatchable;

    public function __construct(public int $planId, public string $planName) {}
}
