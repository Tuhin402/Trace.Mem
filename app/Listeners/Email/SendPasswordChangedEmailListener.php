<?php

namespace App\Listeners\Email;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * SendPasswordChangedEmailListener
 *
 * Listens to Illuminate\Auth\Events\PasswordReset — fired by Laravel/Fortify
 * AFTER the user's password has been successfully reset.
 *
 * Note: this is the "password was changed" confirmation email.
 * The "password reset link" email is handled by User::sendPasswordResetNotification().
 */
class SendPasswordChangedEmailListener
{
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        if (! $user || ! $user->email) {
            return;
        }

        SendEmailJob::dispatch(
            template:       EmailTemplate::PasswordChanged,
            data:           [
                'user_name'      => $user->name,
                'changed_at'     => now()->format('M j, Y \a\t g:i A T'),
                'security_url'   => url('/settings/security'),
                'support_email'  => 'support@tracemem.one',
            ],
            recipientEmail: $user->email,
            userId:         $user->id,
            requestId:      Str::uuid()->toString(),
        );
    }
}
