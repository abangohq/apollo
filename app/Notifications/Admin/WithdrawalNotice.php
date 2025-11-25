<?php

namespace App\Notifications\Admin;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WithdrawalNotice extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Withdrawal $withdrawal)
    {
        $this->afterCommit();
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = <<<EOT
            <table width="100%">
                <tr>
                    <td>Amount:</td>
                    <td>{$this->withdrawal->amount}</td>
                </tr>
                <tr>
                    <td>Reference:</td>
                    <td>{$this->withdrawal->reference}</td>
                </tr>
                <tr>
                    <td>Account No:</td>
                    <td>{$this->withdrawal->account_number}</td>
                </tr>
                <tr>
                    <td>Account Name:</td>
                    <td>{$this->withdrawal->account_name}</td>
                </tr>
                <tr>
                    <td>Bank name:</td>
                    <td>{$this->withdrawal->bank_name}</td>
                </tr>
            </table>
        EOT;

        return (new MailMessage)
            ->greeting("Hi {$notifiable->name},")
            ->line('There is a new withdrawal via the manual channels that needs to be completed please login into the admin dashboard to complete transactions.')
            ->line("The user with the email {$this->withdrawal->user->email} is requesting to withdraw the sum of {$this->withdrawal->amount}. see withdrawal details below")
            ->line(new HtmlString($template))
            ->action('Dashboard', url('/'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
