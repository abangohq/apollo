<?php

namespace App\Notifications\User;

use App\Models\CryptoTransaction;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class CryptoPendingNotification extends Notification implements ShouldQueue
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
            ->subject("Your {$this->cryptoTransaction->crypto} Deposit is Being Processed")
            ->greeting("Hi {$notifiable->username}")
            ->line("We have received your deposit of {$this->cryptoTransaction->crypto_amount} {$this->cryptoTransaction->crypto} and it is currently being processed.")
            ->line("You'll get another notification once the transaction is confirmed and your cash is in your wallet.")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Your Crypto Deposit is Being Processed")
            ->withBody("We have received your deposit of {$this->cryptoTransaction->crypto_amount} {$this->cryptoTransaction->crypto} and it is currently being processed.")
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
            'message' => "We have received a pending notification of your crypto deposit of {$this->cryptoTransaction->crypto} {$this->cryptoTransaction->crypto_amount} you will notified once it successful."
        ];
    }
}
