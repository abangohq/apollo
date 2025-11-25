<?php

namespace App\Notifications\User;

use App\Models\CableTopUp;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CableReversalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CableTopUp $cableTopUp)
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
            ->subject('Cable Tv Plan Purchase Reversal')
            ->greeting("Hi {$notifiable->username}")
            ->line("We are unable to complete Your purchase of {$this->cableTopUp->amount_paid} {$this->cableTopUp->product} and your funds has been reversed to your account.")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Cable TV plan Purchase Reversal")
            ->withBody("We are unable to complete Your purchase of {$this->cableTopUp->amount_paid} {$this->cableTopUp->product} and your funds has been reversed to your account.")
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
            'title' => "Cable TV plan Purchase Reversal",
            'message' => "We are unable to complete Your purchase of {$this->cableTopUp->amount_paid} {$this->cableTopUp->product} and your funds has been reversed to your account."
         ];
    }
}
