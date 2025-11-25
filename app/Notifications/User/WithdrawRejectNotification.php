<?php

namespace App\Notifications\User;

use App\Models\Withdrawal;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawRejectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ?string $reason, public Withdrawal $withdrawal)
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
        return ['mail', 'firebase', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');

        return (new MailMessage)
            ->subject('Withdrawal Request reject')
            ->greeting("Hi {$notifiable->username}")
            ->line("Your withdrawal request of {$this->withdrawal->amount} was declined and your funds has been reversed back to your account.")
            ->line("Your withdrawal was rejected due to the reasons stated below please reach out to use via our support channels")
            ->line("{$this->reason}")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Withdrawal Request")
            ->withBody("Your withdrawal request of {$this->withdrawal->amount} was declined and your funds has been reversed back to your account.")
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
            'title' => "Withdrawal Request",
            'message' => "Your withdrawal request of {$this->withdrawal->amount} was declined and your funds has been reversed back to your account."
        ];
    }
}
