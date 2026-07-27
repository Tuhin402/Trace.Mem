<?php

namespace App\Services\Control\Communications;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Models\OperationalCommunicationLog;
use App\Models\AdminAuditLog;
use App\Services\Control\Communications\Channels\EmailChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommunicationService
{
    public function __construct(
        private EmailChannel $emailChannel
    ) {}

    public function dispatch(CommunicationContext $context): bool
    {
        // Rate Limiting (10 per minute per admin by default)
        $rateLimit = config('control.communications.rate_limit', 10);
        $rateKey = 'comm:rate:' . $context->senderId;
        
        if (Cache::get($rateKey, 0) >= $rateLimit) {
            Log::warning("Admin {$context->senderId} exceeded communication rate limit.");
            return false;
        }

        // Idempotency Lock
        $idempotencyKey = 'comm:lock:' . md5($context->senderId . $context->recipientUuid . $context->template->value);
        if (!Cache::add($idempotencyKey, true, 60)) {
            Log::info("Idempotency lock prevented duplicate communication for {$context->recipientUuid}");
            return false;
        }

        // Increment Rate Limit
        Cache::add($rateKey, 0, 60);
        Cache::increment($rateKey);

        // Render Blade for immutability
        $renderedBody = view('emails.control.communication', [
            'theme' => new \App\Services\Email\EmailTheme(),
            'subject' => $context->subject,
            'body' => $context->body,
            'appName' => config('app.name', 'Trace.Mem'),
            'appUrl' => config('app.url', 'https://tracemem.one'),
            'support_email' => 'noreply@contact.tracemem.one',
            'currentYear' => date('Y'),
        ])->render();

        // Create Communication Log
        $log = OperationalCommunicationLog::create([
            'id' => Str::uuid(),
            'recipient_uuid' => $context->recipientUuid,
            'recipient_type' => $context->recipientType,
            'recipient_email' => $context->recipientEmail,
            'recipient_name' => $context->recipientName,
            'sender_id' => $context->senderId,
            'sender_name' => $context->senderName,
            'channel' => $context->channel,
            'template_category' => $context->template->category(),
            'template_name' => $context->template->value,
            'rendered_subject' => $context->subject,
            'rendered_body' => $renderedBody,
            'status' => 'queued',
        ]);

        // Create Admin Audit Log
        AdminAuditLog::create([
            'user_id' => $context->senderId,
            'action' => 'sent_operational_communication',
            'entity_type' => OperationalCommunicationLog::class,
            'entity_id' => $log->id,
            'new_values' => [
                'recipient' => $context->recipientUuid,
                'recipient_type' => $context->recipientType,
                'channel' => $context->channel,
                'template' => $context->template->value,
                'subject' => $context->subject,
                'communication_log_uuid' => $log->id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Clear Recipient History Cache
        Cache::forget("control:communications:history:{$context->recipientType}:{$context->recipientUuid}");

        // Dispatch via appropriate channel
        if ($context->channel === 'email') {
            $this->emailChannel->dispatch($context, $log->id);
        }

        return true;
    }
}
