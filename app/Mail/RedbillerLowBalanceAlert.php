<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RedbillerLowBalanceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $balance;

    public function __construct(float $balance)
    {
        $this->balance = $balance;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Redbiller Low Balance Alert',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'mail.redbiller-low-balance',
            with: [
                'balance' => number_format($this->balance, 2),
            ]
        );
    }
}