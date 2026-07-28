<?php

namespace App\Notifications\Billing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FoundingOfferGrantedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Your Founding Offer is Active')
                    ->greeting('Hello ' . $notifiable->name . '!')
                    ->line('Great news! We have manually activated the Founding Offer on your account.')
                    ->line('You now have access to premium features and benefits.')
                    ->action('Go to Dashboard', url('/app/dashboard'))
                    ->line('Thank you for being part of our journey!');
    }
}
