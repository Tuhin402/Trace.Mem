<?php

namespace App\Events\Control\Communications;

use App\Models\OperationalCommunicationLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OperationalEmailFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OperationalCommunicationLog $log,
        public Throwable $exception
    ) {}
}
