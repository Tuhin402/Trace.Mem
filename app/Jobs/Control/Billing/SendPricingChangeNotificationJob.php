<?php

namespace App\Jobs\Control\Billing;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Enums\CommunicationTemplate;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Control\Communications\CommunicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPricingChangeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
        public readonly int $planId,
        public readonly string $period,
        public readonly string $oldAmount,
        public readonly string $newAmount,
        public readonly int $senderId,
    ) {
        $this->onQueue('billing');
    }

    public function handle(CommunicationService $communications): void
    {
        $user = User::query()->findOrFail($this->userId);
        $sender = User::query()->findOrFail($this->senderId);
        $plan = SubscriptionPlan::query()->findOrFail($this->planId);
        $period = ucfirst($this->period);

        $body = "Hello {$user->name},\n\nWe have updated the {$period} list price for the {$plan->name} plan from ₹{$this->oldAmount} to ₹{$this->newAmount}. Your current subscription remains grandfathered at ₹{$this->oldAmount}; this update does not change your existing subscription.\n\nWe are making this change to support continued investment in TraceMem. Please reply to this email if you have any questions.\n\nBest regards,\nThe TraceMem Team";

        $communications->dispatch(new CommunicationContext(
            recipientUuid: $user->uuid ?? "user-{$user->id}",
            recipientType: 'user',
            recipientEmail: $user->email,
            recipientName: $user->name,
            senderId: $sender->id,
            senderName: $sender->name,
            template: CommunicationTemplate::PricingChangeNotice,
            subject: CommunicationTemplate::PricingChangeNotice->defaultSubject(),
            body: $body,
            metadata: [
                'plan_id' => $plan->id,
                'period' => $this->period,
                'old_amount' => $this->oldAmount,
                'new_amount' => $this->newAmount,
            ],
        ));
    }
}
