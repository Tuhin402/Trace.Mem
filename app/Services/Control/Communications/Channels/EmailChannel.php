<?php

namespace App\Services\Control\Communications\Channels;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Jobs\Control\Communications\SendOperationalEmailJob;

class EmailChannel implements CommunicationChannel
{
    public function dispatch(CommunicationContext $context, string $logId): void
    {
        SendOperationalEmailJob::dispatch($context, $logId);
    }
}
