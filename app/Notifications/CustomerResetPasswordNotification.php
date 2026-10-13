<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * API-friendly password reset notification: carries the raw token for the
 * client app to submit back to POST /api/v1/customer/auth/reset-password,
 * rather than a clickable frontend URL (which does not exist here).
 */
class CustomerResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your password')
            ->greeting("Hi {$notifiable->name},")
            ->line('We received a request to reset your password. Use the code below to continue:')
            ->line(new HtmlString("<strong style=\"font-size:20px; letter-spacing:2px;\">{$this->token}</strong>"))
            ->line('This code expires in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
