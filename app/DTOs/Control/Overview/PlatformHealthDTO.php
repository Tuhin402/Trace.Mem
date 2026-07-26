<?php

namespace App\DTOs\Control\Overview;

use Illuminate\Contracts\Support\Arrayable;

class PlatformHealthDTO implements Arrayable
{
    /**
     * @param array<array{name: string, status: string, color: string, bg: string, border: string}> $services
     */
    public function __construct(
        public readonly array $services
    ) {}

    public function toArray(): array
    {
        return [
            'services' => $this->services,
        ];
    }
}
