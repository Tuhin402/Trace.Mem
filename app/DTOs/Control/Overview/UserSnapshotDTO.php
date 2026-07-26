<?php

namespace App\DTOs\Control\Overview;

use Illuminate\Contracts\Support\Arrayable;

class UserSnapshotDTO implements Arrayable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $status,
        public readonly string $role,
        public readonly string $time
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->role,
            'time' => $this->time,
        ];
    }
}
