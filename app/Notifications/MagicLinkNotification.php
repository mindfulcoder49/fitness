<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(private string $loginUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your sign-in link')
            ->greeting('Hello!')
            ->line('Click the button below to sign in. This link expires in 15 minutes and can only be used once.')
            ->action('Sign in', $this->loginUrl)
            ->line('If you did not request this link, no action is needed.');
    }
}
