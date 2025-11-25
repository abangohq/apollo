<?php

namespace App\Notifications\User;

use App\Models\SwapTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SwapPayinAddressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SwapTransaction $swapTransaction)
    {
        $this->queue = 'notifications';
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $from = strtoupper($this->swapTransaction->currency_from);
        $to = strtoupper($this->swapTransaction->currency_to);
        $amountFrom = $this->swapTransaction->amount_expected_from;
        $payinAddress = $this->swapTransaction->payin_address;
        $payoutAddress = $this->swapTransaction->payout_address;
        $trackUrl = $this->swapTransaction->track_url;
        $payTill = $this->swapTransaction->pay_till;

        $mail = (new MailMessage)
            ->subject('Your Crypto Swap Pay-in Address')
            ->greeting('Hello!')
            ->line("Your swap has been created successfully.")
            ->line("Pair: {$from} → {$to}")
            ->line("Amount to send: {$amountFrom} {$from}")
            ->line("Pay-in address: {$payinAddress}");

        if (!empty($payoutAddress)) {
            $mail->line("Payout address: {$payoutAddress}");
        }

        if (!empty($trackUrl)) {
            $mail->action('Track your swap', $trackUrl);
        }

        if (!empty($payTill)) {
            $mail->line("Please make the payment before: {$payTill}");
        }

        return $mail
            ->line('Make sure to send the exact amount to the pay-in address to avoid delays.')
            ->line('If you have any questions, reply to this email or contact support.')
            ->line('Reference: ' . $this->swapTransaction->reference);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Crypto Swap Pay-in Address',
            'body' => 'Your swap has been created. Please send funds to the provided pay-in address.',
            'reference' => $this->swapTransaction->reference,
            'currency_from' => $this->swapTransaction->currency_from,
            'currency_to' => $this->swapTransaction->currency_to,
            'payin_address' => $this->swapTransaction->payin_address,
            'payout_address' => $this->swapTransaction->payout_address,
            'track_url' => $this->swapTransaction->track_url,
        ];
    }
}