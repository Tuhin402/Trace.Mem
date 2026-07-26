<?php

namespace App\DTOs\Control\Overview;

use Illuminate\Contracts\Support\Arrayable;

class WidgetErrorDTO implements Arrayable
{
    public function __construct(
        public readonly string $error,
        public readonly string $message = 'Widget temporarily unavailable.'
    ) {}

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'message' => $this->message,
        ];
    }
}
