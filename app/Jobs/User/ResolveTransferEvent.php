<?php

namespace App\Jobs\User;

use App\Enums\Tranx;
use App\Services\Payment\MonnifyService;
use App\Services\Payment\RedbillerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveTransferEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload, public string $provider)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(MonnifyService $monnify, RedbillerService $redbiller): void
    {                
        if ($this->provider == Tranx::WD_MONNIFY->value) {
            $monnify->resolveEvent($this->payload);
        } elseif($this->provider == Tranx::WD_REDBILLER->value) {
            $redbiller->resolveEvent($this->payload);
        }
    }
}
