<?php

namespace App\Notifications\User;

use App\Models\ReferralCode;
use GGInnovative\Larafirebase\Messages\FirebaseMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BonusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ReferralCode $referralCode)
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
        return ['mail', 'database', 'firebase'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');

        return (new MailMessage)
            ->subject('Referral Bonus')
            ->greeting("Hi {$notifiable->name}")
            ->line("You have been reward with NGN {$this->referralCode->amount} for registering with the referral code {$this->referralCode->code}")
            ->line("Thank you  for using {$appName}");
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase(object $notifiable)
    {
        return (new FirebaseMessage)
            ->withTitle("Referral Bonus")
            ->withBody("You have been reward with NGN {$this->referralCode->amount} for registering with the referral code {$this->referralCode->code}")
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
            'title' => 'Referral Bonus',
            'message' => "You have been reward with NGN {$this->referralCode->amount} for registering with the referral code {$this->referralCode->code}",
        ];
    }
}

