<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PasswordResetToken extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public array $payload)
    {
        $this->queue = 'notifications';
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');
        $name = $notifiable?->name ?? $notifiable->username;

        return (new MailMessage)
            ->subject("Your $appName One Time Password")
            ->greeting("Hi {$name},")
            ->line("To proceed further with your password reset process on {$appName} please use the OTP below")
            ->line(new HtmlString('<p style="text-align:center"><code style="background-color: #f0f0f0; padding: 8px 18px; font-weight: bold; font-size: 24px; border-radius: 10px;">' . $this->payload['token'] . '</code></p>'))
            ->line('Please note that this OTP will only be valid for 180 seconds');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
