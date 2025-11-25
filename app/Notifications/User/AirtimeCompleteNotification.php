<?php

namespace App\Notifications\User;

use App\Models\AirtimeTopUp;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AirtimeCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public AirtimeTopUp $airtimeTopUp)
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
        $appName = env('APP_NAME');

        return (new MailMessage)
            ->subject('Airtime Purchase')
            ->greeting("Hi {$notifiable->username}")
            ->line("Your purchase of {$this->airtimeTopUp->amount_paid} {$this->airtimeTopUp->product} has been completed successfully.")
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Airtime Purchase")
            ->withBody("Your purchase of {$this->airtimeTopUp->amount_paid} {$this->airtimeTopUp->product} has been completed successfully.")
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
            'title' => 'Airtime Purchase',
            'message' => "Your purchase of {$this->airtimeTopUp->amount_paid} {$this->airtimeTopUp->product} has been completed successfully."
        ];
    }
}
