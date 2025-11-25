<?php

namespace App\Notifications\User;

use App\Models\SwapTransaction;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SwapStateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SwapTransaction $swapTransaction)
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
     * Get the message for this notification
     */
    public function noticeMessage()
    {
        $from = strtoupper($this->swapTransaction->currency_from);
        $to = strtoupper($this->swapTransaction->currency_to);

        if (in_array($this->swapTransaction->status, ['overdue', 'expired'])) {
            return "Your crypto swap {$from} - {$to} has been {$this->swapTransaction->status} for payin please do not send in the payin, if you have any complaints please do not hesitate to reach out to us via our support channels.";
        }

        switch (strtolower($this->swapTransaction->status)) {
            case 'confirming':
                return "We have received your payin for crypto swap {$from} - {$to} and awaiting confirmations in other to process your crypto swap.";
                break;

            case 'exchanging':
                return "Your Payment has been confirmed for crypto swap {$from} - {$to} and is being exchanged.";
                break;

            case 'finished':
                return "Your crypto swap {$from} - {$to} has been completed successfully, if you have any complaints please do not hesitate to reach out to us via our support channels.";
                break;

            case 'failed':
                return "Your crypto swap {$from} - {$to} failed, if you have any complaints please do not hesitate to reach out to us via our support channels.";
                break;

            case 'refunded':
                return "Sorry to inform you Your crypto swap {$from} - {$to} was refunded back to your refund address due to some difficulty, if you have any complaints please do not hesitate to reach out to us via our support channels.";
                break;

            default:
                return "Your crypto swap status is currently unknown please reach out to us if transaction is not fullfilled after 3 hours";
                break;
        }
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');

        return (new MailMessage)
            ->subject('Crypto Swap')
            ->greeting("Hi {$notifiable->username}")
            ->line($this->noticeMessage())
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Crypto Swap")
            ->withBody($this->noticeMessage())
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
            'title' => "Crypto Swap",
            'message' => $this->noticeMessage()
        ];
    }
}
