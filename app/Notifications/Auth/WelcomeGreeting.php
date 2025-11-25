<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WelcomeGreeting extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = env('APP_NAME');
        $billImage = asset('images/bills.png');
        $coinImage = asset('images/coin.png');

        $template = <<<EOT
        <table width='100%'>
            <tr>
                <td>
                    <div style='padding:10px'>
                        <img src='$billImage' alt=''>
                    </div>
                    <h3>Pay Bills</h3>
                </td>
                <td>
                    <div style='padding:10px'>
                        <img src='$coinImage' alt=''>
                    </div>
                    <h3>Sell & Swap Crypto</h3>
                </td>
            </tr>
        </table>
        EOT;

        return (new MailMessage)
            ->subject("Welcome To {$appName}")
            ->greeting("Hey, You just joined {$appName}!")
            // ->line("Hi {$notifiable?->name}")
            ->line("Welcome to {$appName} and congrats on starting out your new journey on convenience! {$appName} is the easiest way for you to sell cryptocurrency, and we’re happy you found  it interesting to join us")
            ->line("There’s alot you can do with Koyn, but here’s a highlight of a few important things.")
            ->line(new HtmlString("<h1>Key Highlights</h1>"))
            ->line(new HtmlString($template))
            ->action('Get Started', url('/'));
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
