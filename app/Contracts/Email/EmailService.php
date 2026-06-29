<?php

namespace App\Contracts\Email;

use App\Enums\EmailTemplate;
use App\Models\EmailLog;

/**
 * EmailService — provider-agnostic email sending contract.
 *
 * Controllers, jobs, listeners, and observers depend only on this interface.
 * Swapping Resend for SES, Postmark, or another provider requires only
 * changing the binding in EmailServiceProvider — zero other files change.
 */
interface EmailService
{
    /**
     * Queue and send a transactional email.
     *
     * @param  EmailTemplate  $template      Which email to send
     * @param  array          $data          Template-specific data (name, url, amount, etc.)
     * @param  string         $recipientEmail Destination email address
     * @param  int|null       $userId        Associated user ID (for email_logs FK)
     * @param  string|null    $requestId     X-TraceMem-Request-ID for full traceability
     * @return EmailLog|null  The created log record, or null on catastrophic failure
     */
    public function send(
        EmailTemplate $template,
        array         $data,
        string        $recipientEmail,
        ?int          $userId    = null,
        ?string       $requestId = null,
    ): ?EmailLog;
}
