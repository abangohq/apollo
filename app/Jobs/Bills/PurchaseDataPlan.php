<?php

namespace App\Jobs\Bills;

use App\Models\DataTopUp;
use App\Services\Bills\DataBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurchaseDataPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public DataTopUp $topup)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(DataBillService $service): void
    {
        $topup = $this->topup->refresh();
        $service->handle($topup);
    }
}
