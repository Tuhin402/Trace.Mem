<?php

namespace App\Services\Email;

use App\Contracts\Email\EmailService;
use App\Enums\EmailTemplate;
use App\Mail\EmailTemplateMail;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ResendEmailService — Resend implementation of EmailService.
 *
 * Responsibilities:
 *   1. Create an EmailLog record (status = queued) before attempting send
 *   2. Build and send the Mailable with custom traceability headers
 *   3. Update EmailLog on success (sent) or failure (failed)
 *   4. Never throw — always return EmailLog or null
 *
 * Provider swap: to switch to SES, Postmark, or another provider:
 *   - Create a new XxxEmailService implementing EmailService
 *   - Change the binding in EmailServiceProvider::register()
 *   - Zero other files change
 */
class ResendEmailService implements EmailService
{
    public function send(
        EmailTemplate $template,
        array         $data,
        string        $recipientEmail,
        ?int          $userId    = null,
        ?string       $requestId = null,
    ): ?EmailLog {
        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name');

        // ── 1. Create EmailLog record immediately (status: queued) ────────────
        $log = null;

        try {
            $log = EmailLog::create([
                'user_id'           => $userId,
                'provider'          => 'resend',
                'template_name'     => $template->value,
                'template_version'  => $template->versionedName(),
                'subject'           => $template->subject(),
                'recipient_email'   => $recipientEmail,
                'sender_email'      => $fromAddress,
                'request_id'        => $requestId,
                'status'            => EmailLog::STATUS_QUEUED,
                'queued_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ResendEmailService: failed to create EmailLog', [
                'template'         => $template->value,
                'recipient'        => $recipientEmail,
                'error'            => $e->getMessage(),
            ]);
            // Log creation failure is non-fatal — still attempt send
        }

        // ── 2. Build and send the Mailable ───────────────────────────────────
        try {
            $mailable = new EmailTemplateMail(
                template:   $template,
                data:       $data,
                requestId:  $requestId ?? 'no-request-id',
                logId:      $log?->id,
            );

            Mail::to($recipientEmail)->send($mailable);

            // ── 3a. Update log on success ─────────────────────────────────────
            // provider_message_id is populated later via email.sent webhook
            if ($log) {
                $log->update([
                    'status'  => EmailLog::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            Log::info('ResendEmailService: email sent', [
                'template'    => $template->value,
                'version'     => $template->versionedName(),
                'recipient'   => $recipientEmail,
                'request_id'  => $requestId,
                'log_id'      => $log?->id,
            ]);
        } catch (\Throwable $e) {
            // ── 3b. Update log on failure ─────────────────────────────────────
            if ($log) {
                $log->update([
                    'status'    => EmailLog::STATUS_FAILED,
                    'failed_at' => now(),
                    'metadata'  => array_merge((array) ($log->metadata ?? []), [
                        'error'   => $e->getMessage(),
                        'class'   => get_class($e),
                    ]),
                ]);
            }

            Log::error('ResendEmailService: send failed', [
                'template'   => $template->value,
                'recipient'  => $recipientEmail,
                'request_id' => $requestId,
                'log_id'     => $log?->id,
                'error'      => $e->getMessage(),
            ]);

            // Re-throw so SendEmailJob can handle retries
            throw $e;
        }

        return $log;
    }
}
