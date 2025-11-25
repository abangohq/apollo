<?php

namespace App\Notifications\User;

use App\Models\MeterTopUp;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class MeterCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MeterTopUp $meterTopUp, public ?string $meterToken)
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
            ->subject('Meter Plan Purchase')
            ->greeting("Hi {$notifiable->username}")
            ->line("Your purchase of {$this->meterTopUp->amount_paid} {$this->meterTopUp->product} completed successfully. below is your meter top up token")
            ->line(new HtmlString('<p style="text-align:center"><code style="background-color: #f0f0f0; padding: 8px 18px; font-weight: bold; font-size: 22px; border-radius: 10px;">' . $this->meterToken . '</code></p>'))
            ->line("Thank you for choosing {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Meter Plan Purchase")
            ->withBody("Your purchase of {$this->meterTopUp->amount_paid} {$this->meterTopUp->product} completed successfully. and your token is: {$this->meterToken}")
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
            'title' => "Meter Plan Purchase",
            'message' => "Your purchase of {$this->meterTopUp->amount_paid} {$this->meterTopUp->product} completed successfully. and your token is: {$this->meterToken}"
        ];
    }
}
