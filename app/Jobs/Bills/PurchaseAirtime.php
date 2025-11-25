<?php

namespace App\Jobs\Bills;

use App\Models\AirtimeTopUp;
use App\Services\Bills\AirtimeBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurchaseAirtime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public AirtimeTopUp $topup)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(AirtimeBillService $service): void
    {
        $topup  = $this->topup->refresh();
        $service->handle($topup);
    }
}
