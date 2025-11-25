<?php

namespace App\Jobs\Bills;

use App\Models\CableTopUp;
use App\Services\Bills\CableBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurchaseCablePlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public CableTopUp $topup)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(CableBillService $cableService): void
    {
        $topup = $this->topup->refresh();
        $cableService->handle($topup);
    }
}
