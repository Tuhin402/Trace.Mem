<?php

namespace App\Mail;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Services\Email\EmailTheme;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class OperationalEmailMail extends Mailable
{
    public function __construct(
        public readonly CommunicationContext $context,
        public readonly string $logId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->context->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.control.communication',
            with: [
                'theme'         => new EmailTheme(),
                'subject'       => $this->context->subject,
                'body'          => $this->context->body,
                'appName'       => config('app.name', 'Trace.Mem'),
                'appUrl'        => config('app.url', 'https://tracemem.one'),
                'support_email' => 'noreply@contact.tracemem.one',
                'currentYear'   => date('Y'),
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: null,
            references: [],
            text: array_filter([
                'X-TraceMem-Template'   => 'OperationalCommunication',
                'X-TraceMem-Log-ID'     => $this->logId,
            ]),
        );
    }
}
