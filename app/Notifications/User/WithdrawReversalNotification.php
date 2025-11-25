<?php

namespace App\Notifications\User;

use App\Models\Withdrawal;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawReversalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Withdrawal $withdrawal)
    {
        $this->queue = 'notifications';
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'firebase', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');

        return (new MailMessage)
            ->subject('Withdrawal Reversal')
            ->greeting("Hi {$notifiable->username}")
            ->line("We are unable to complete your withdrawal of {$this->withdrawal->amount} and your funds has been reversed to your wallet.")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Withdrawal Request Reversal")
            ->withBody("We are unable to complete your withdrawal of {$this->withdrawal->amount} and your funds has been reversed to your wallet.")
            ->withToken($notifiable->device_token)
            ->asNotification();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Withdrawal Request Reversal",
            'message' => "We are unable to complete your withdrawal of {$this->withdrawal->amount} and your funds has been reversed to your wallet."
        ];
    }
}
