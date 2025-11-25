<?php

namespace App\Jobs\Crypto;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\SwapTransaction;
use App\Models\WalletTransaction;
use App\Notifications\User\SwapStateNotification;
use App\Services\Crypto\ChangellyService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ResolveSwapTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public SwapTransaction $swapTransaction)
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
        return [(new WithoutOverlapping($this->swapTransaction->reference))->releaseAfter(60)];
    }

    /**
     * Execute the job.
     */
    public function handle(ChangellyService $changelly): void
    {
        $response = rescue(fn () => $changelly->swapStatus($this->swapTransaction->swap_tranx_id));

        if (is_null($response) || array_key_exists('error', $response)) {
            if ($this->shouldBackoff()) {
                return;
            }

            Self::dispatch($this->swapTransaction)->delay(now()->addMinutes(env('SWAP_RETRY', 5)));
            return;
        }

        $status = $response['result'];
        $this->swapTransaction->update(['status' => $status]);

        // Map Changelly status to wallet transaction status
        $walletStatus = match ($status) {
            'finished' => Status::SUCCESSFUL,
            'failed', 'overdue', 'expired', 'refunded' => Status::FAILED,
            default => Status::PENDING,
        };

        // Update corresponding wallet transaction
        WalletTransaction::where('transaction_type', Tranx::SWAP)
            ->where('transaction_id', $this->swapTransaction->id)
            ->update(['status' => $walletStatus]);

        if (in_array($status, ['confirming', 'exchanging', 'finished', 'failed', 'refunded', 'overdue', 'expired'])) {
            $this->swapTransaction->user->notify(new SwapStateNotification($this->swapTransaction));
        }

        if (!in_array($status, ['finished', 'failed', 'overdue', 'expired', 'refunded']) && !$this->shouldBackoff()) {
            Self::dispatch($this->swapTransaction)->delay(now()->addMinutes(env('SWAP_RETRY', 5)));
            return;
        }
    }

    /**
     * Check if it should backoff
     */
    public function shouldBackoff()
    {
        if ($this->isExpired()) {
            logger()->alert('Unable to fullfill a swap transaction', $this->swapTransaction->toArray());
            return true;
        }

        return false;
    }

    /**
     * Determine if the swap should be considered expired.
     * Uses provider pay_till if available, otherwise 3 hours TTL with optional grace.
     */
    protected function isExpired(): bool
    {
        $graceMinutes = (int) env('SWAP_EXPIRY_GRACE_MINUTES', 5);
        $expiryHours = (int) env('SWAP_EXPIRY_HOURS', 3);

        try {
            if (!empty($this->swapTransaction->pay_till)) {
                $deadline = Carbon::parse($this->swapTransaction->pay_till)->addMinutes($graceMinutes);
            } else {
                $deadline = Carbon::parse($this->swapTransaction->created_at)->addHours($expiryHours)->addMinutes($graceMinutes);
            }
        } catch (\Throwable $e) {
            $deadline = Carbon::parse($this->swapTransaction->created_at)->addHours($expiryHours)->addMinutes($graceMinutes);
        }

        return now()->greaterThan($deadline);
    }
}
