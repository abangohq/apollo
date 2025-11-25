<?php

namespace App\Jobs\User;

use App\Models\Withdrawal;
use App\Services\Payment\TransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Traits\Conditionable;

class ProcessWithdraw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Conditionable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public Withdrawal $withdrawal)
    {
        $this->afterCommit();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->withdrawal->reference)];
    }

    /**
     * Execute the job.
     */
    public function handle(TransferService $transfer): void
    {
        $withdrawal = $this->withdrawal->refresh();
        $transfer->handle($withdrawal);
    }
}
