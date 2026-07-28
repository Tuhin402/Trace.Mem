<?php

namespace App\Events\Billing;

use App\Models\User;
use App\Models\UserBillingOverride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoundingOfferOverrideGranted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public UserBillingOverride $override
    ) {}
}
