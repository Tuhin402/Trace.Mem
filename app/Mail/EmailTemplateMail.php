<?php

namespace App\Mail;

use App\Enums\EmailTemplate;
use App\Services\Email\EmailTheme;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * EmailTemplateMail — single Mailable for all transactional emails.
 *
 * The EmailTemplate enum determines which Blade view is rendered.
 * EmailTheme provides all brand tokens to the view — no colors are hardcoded.
 *
 * Custom headers applied to every outbound email:
 *   X-TraceMem-Template   — identifies the email type in Resend's dashboard
 *   X-TraceMem-Request-ID — links queue job → email_log → Resend → webhook
 *   X-TraceMem-Log-ID     — direct reference to the email_logs.id record
 */
class EmailTemplateMail extends Mailable
{
    public function __construct(
        public readonly EmailTemplate $template,
        public readonly array         $data,
        public readonly string        $requestId,
        public readonly ?int          $logId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->template->view(),
            with: array_merge($this->data, [
                'theme'       => new EmailTheme(),
                'subject'     => $this->template->subject(),
                'appName'     => config('app.name', 'Trace.Mem'),
                'appUrl'      => config('app.url', 'https://tracemem.one'),
                'supportEmail' => 'support@tracemem.one',
                'currentYear' => date('Y'),
            ]),
        );
    }

    public function headers(): Headers
    {
        $tags = ['x-tracemem-template' => $this->template->headerName()];

        return new Headers(
            messageId: null,
            references: [],
            text: array_filter([
                'X-TraceMem-Template'   => $this->template->headerName(),
                'X-TraceMem-Request-ID' => $this->requestId,
                'X-TraceMem-Log-ID'     => $this->logId ? (string) $this->logId : null,
            ]),
        );
    }
}
