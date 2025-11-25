<?php

namespace App\Jobs\Crypto;

use App\Services\Crypto\XprocessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class  ResolveXprocessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload)
    {
        $this->queue = 'crypto-operations';
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->payload['PaymentId']))->releaseAfter(60)];
    }

    /**
     * Execute the job.
     */
    public function handle(XprocessingService $xprocessingService): void
    {
        try {
            $xprocessingService->webhook($this->payload);
        } catch (\RangeException $th) {
            self::dispatch($this->payload)->delay(now()->addMinutes(30));
        }
    }
}
