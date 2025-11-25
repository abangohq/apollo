<?php

namespace App\Notifications\User;

use App\Models\CryptoTransaction;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CryptoCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CryptoTransaction $cryptoTransaction)
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
            ->greeting("Hi {$notifiable->username}")
            ->line("Your deposit of {$this->cryptoTransaction->crypto} {$this->cryptoTransaction->crypto_amount} has been successfully exchange and NGN{$this->cryptoTransaction->payout_amount} has been deposited in your wallet.")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Crypto Deposit")
            ->withBody("Your deposit of {$this->cryptoTransaction->crypto} {$this->cryptoTransaction->crypto_amount} has been successfully exchange and NGN{$this->cryptoTransaction->payout_amount} has been deposited in your wallet.")
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
            'title' => "Crypto Deposit",
            'message' => "Your deposit of {$this->cryptoTransaction->crypto} {$this->cryptoTransaction->crypto_amount} has been successfully exchange and NGN{$this->cryptoTransaction->payout_amount} has been deposited in your wallet."
        ];
    }
}
