<?php

namespace App\Jobs\Bills;

use App\Models\MeterTopUp;
use App\Services\Bills\MeterBillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ResolveMeterPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public MeterTopUp $topup)
    {
        $this->delay(now()->addMinutes(5));
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->topup->reference))->releaseAfter(60)];
    }

    /**
     * Execute the job.
     */
    public function handle(MeterBillService $service): void
    {
        $topup = $this->topup->refresh();
        $service->resolve($topup);
    }
}
