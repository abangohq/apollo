<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class FlaggedTransactionNotice extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $type,
        public User $user,
        public string $reference,
        public int|float $amount,
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = URL::to('/');

        return (new MailMessage)
            ->subject('Flagged Transaction Pending Review')
            ->greeting("Hi {$notifiable->name},")
            ->line('A transaction was initiated by a flagged user or wallet and has been pended for manual review.')
            ->line("Type: {$this->type}")
            ->line("User: {$this->user->email} ({$this->user->username})")
            ->line("Amount: {$this->amount}")
            ->line("Reference: {$this->reference}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Flagged Transaction Pending Review',
            'type' => $this->type,
            'user_id' => $this->user->id,
            'reference' => $this->reference,
            'amount' => $this->amount,
        ];
    }
}