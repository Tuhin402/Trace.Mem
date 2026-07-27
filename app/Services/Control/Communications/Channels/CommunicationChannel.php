<?php

namespace App\Services\Control\Communications\Channels;

use App\DTOs\Control\Communications\CommunicationContext;

interface CommunicationChannel
{
    /**
     * Dispatch the communication context via the specific channel.
     */
    public function dispatch(CommunicationContext $context, string $logId): void;
}
