<?php

namespace App\Jobs\Crypto;

use App\Services\Crypto\VaultodyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveVaultody implements ShouldQueue
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
     * Execute the job.
     */
    public function handle(VaultodyService $vaultody): void
    {
        try {
            $vaultody->webhook($this->payload);
        } catch (\RangeException $th) {
            logger()->debug($th);
            // self::dispatch($this->payload)->delay(now()->addMinutes(5));
        }
    }
}
