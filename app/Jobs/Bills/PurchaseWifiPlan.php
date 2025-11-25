<?php

namespace App\Jobs\Bills;

use App\Models\WifiTopUp;
use App\Services\Bills\WifiBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurchaseWifiPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public WifiTopUp $topup)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(WifiBillService $service): void
    {
        $topup = $this->topup->refresh();
        $service->handle($topup);
    }
}
