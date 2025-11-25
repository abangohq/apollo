<?php

namespace App\Jobs\Bills;

use App\Models\BettingTopUp;
use App\Services\Bills\BettingBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurchaseBetting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public BettingTopUp $topup)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BettingBillService $service): void
    {
        $topup = $this->topup->refresh();
        $service->handle($topup);
    }
}
