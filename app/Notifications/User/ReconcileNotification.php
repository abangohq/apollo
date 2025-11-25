<?php

namespace App\Notifications\User;

use App\Enums\Tranx;
use App\Models\Reconciliation;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReconcileNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The  notification message to send
     * 
     * @var string $notifMessage
     */
    public string $notifMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Reconciliation $reconciliation)
    {
        $this->queue = 'notifications';
        $this->afterCommit();
        
        $this->notifMessage = match ($this->reconciliation->entry) {
            Tranx::DEBIT->value => "NGN {$this->reconciliation->amount} was {$this->reconciliation->entry}ed from your wallet balance for a reconcilation action",
            Tranx::CREDIT->value => "NGN {$this->reconciliation->amount} was {$this->reconciliation->entry}ed to your wallet balance for a reconciliation action",
        };
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
            ->subject('Wallet Reconciliation')
            ->greeting("Hi {$notifiable->username}")
            ->line($this->notifMessage)
            ->line($this->reconciliation->reason)
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Wallet Reconciliation")
            ->withBody($this->notifMessage)
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
            'title' => "Wallet Reconciliation",
            'message' => $this->notifMessage
        ];
    }
}
