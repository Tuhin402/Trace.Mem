<?php

namespace App\Jobs;

use App\Contracts\Email\EmailService;
use App\Enums\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendEmailJob — single queue job for all transactional emails.
 *
 * Architecture: one job, one enum (EmailTemplate), one data array.
 * Replaces 14 individual email job classes with a single, testable unit.
 *
 * Queue: 'emails' — dedicate a Redis queue slot for email delivery,
 * isolated from billing-critical (high) and analytics (default) work.
 *
 * Retry strategy:
 *   3 attempts with exponential backoff: 30s, 120s, 300s
 *   If all 3 fail, the job lands in failed_jobs for inspection.
 *
 * Idempotency: the EmailService creates an EmailLog record at dispatch time.
 * If the same job is retried, ResendEmailService re-sends and updates the
 * existing EmailLog — no duplicate DB records are created because the log
 * already exists and is updated in-place.
 */
class SendEmailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** Total attempts before landing in failed_jobs */
    public int $tries = 3;

    /** Exponential backoff: 30s → 120s → 300s */
    public array $backoff = [30, 120, 300];

    /** Maximum execution time in seconds */
    public int $timeout = 30;

    public function __construct(
        public readonly EmailTemplate $template,
        public readonly array         $data,
        public readonly string        $recipientEmail,
        public readonly ?int          $userId    = null,
        public readonly ?string       $requestId = null,
    ) {
        $this->onQueue('emails');
    }

    public function handle(EmailService $emailService): void
    {
        $emailService->send(
            template:       $this->template,
            data:           $this->data,
            recipientEmail: $this->recipientEmail,
            userId:         $this->userId,
            requestId:      $this->requestId,
        );
    }

    /**
     * Handle a job failure after all retries are exhausted.
     * Logged at 'error' level — alert on this in production monitoring.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob: all retries exhausted', [
            'template'   => $this->template->value,
            'recipient'  => $this->recipientEmail,
            'user_id'    => $this->userId,
            'request_id' => $this->requestId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
