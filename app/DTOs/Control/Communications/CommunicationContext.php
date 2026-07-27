<?php

namespace App\DTOs\Control\Communications;

use App\Enums\CommunicationTemplate;

readonly class CommunicationContext
{
    public function __construct(
        public string $recipientUuid,
        public string $recipientType,
        public string $recipientEmail,
        public string $recipientName,
        public int $senderId,
        public string $senderName,
        public CommunicationTemplate $template,
        public string $subject,
        public string $body,
        public string $channel = 'email',
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'recipient_uuid' => $this->recipientUuid,
            'recipient_type' => $this->recipientType,
            'recipient_email' => $this->recipientEmail,
            'recipient_name' => $this->recipientName,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'template' => $this->template->value,
            'subject' => $this->subject,
            'body' => $this->body,
            'channel' => $this->channel,
            'metadata' => $this->metadata,
        ];
    }
}
