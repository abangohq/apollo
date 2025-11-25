<?php

namespace App\Jobs\User;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WithdrawReversal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Withdrawal $withdrawal)
    {
        $this->delay(now()->addMinutes(5));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->withdrawal->refresh();
    }
}
