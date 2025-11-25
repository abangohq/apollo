<?php

namespace App\Notifications\User;

use App\Models\WalletTransaction;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public WalletTransaction $transaction)
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
        return ['mail', 'database', 'firebase'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Withdrawal Request')
            ->greeting("Hi {$notifiable->name}")
            ->line("Your withdrawal request of {$this->transaction->amount} has been initiated and this amount will be credited to your bank account.")
            ->line('You will be notified when your withdraw request has been completed.');
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Withdrawal Request")
            ->withBody("Your withdrawal request of {$this->transaction->amount} has been initiated and this amount will be credited to your bank account.")
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
            'title' => 'Withdrawal Request',
            'message' => "Your withdrawal request of {$this->transaction->amount} has been initiated and this amount will be credited to your bank account.",
        ];
    }
}
