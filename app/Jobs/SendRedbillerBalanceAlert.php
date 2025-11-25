<?php

namespace App\Jobs;

use App\Services\PlunkEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\RedbillerLowBalanceAlert;

class SendRedbillerBalanceAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $balance;

    public function __construct(float $balance)
    {
        $this->balance = $balance;
    }

    public function handle()
    {
        Mail::to(config('app.admin_email'))->send(new RedbillerLowBalanceAlert($this->balance));
    }
}