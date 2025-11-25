<?php

namespace App\Console\Commands;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\SwapTransaction;
use App\Models\WalletTransaction;
use App\Notifications\User\SwapStateNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireStaleSwaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swap:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire swap transactions with no pay-in after configured TTL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiryHours = (int) env('SWAP_EXPIRY_HOURS', 3);
        $graceMinutes = (int) env('SWAP_EXPIRY_GRACE_MINUTES', 5);
        $now = Carbon::now();
        $expiredCount = 0;

        SwapTransaction::query()
            // Expire swaps that are still awaiting initial pay-in
            // Support legacy 'new' records alongside normalized 'waiting'
            ->whereIn('status', ['waiting', 'new'])
            ->where(function ($q) use ($now, $expiryHours, $graceMinutes) {
                $q->whereNotNull('pay_till')
                    ->where('pay_till', '<', $now->copy()->subMinutes($graceMinutes));

                $q->orWhere(function ($qq) use ($now, $expiryHours, $graceMinutes) {
                    $qq->whereNull('pay_till')
                        ->where('created_at', '<', $now->copy()->subHours($expiryHours)->subMinutes($graceMinutes));
                });
            })
            ->orderBy('id')
            ->chunkById(500, function ($swaps) use (&$expiredCount) {
                foreach ($swaps as $swap) {
                    $swap->update(['status' => 'expired']);

                    WalletTransaction::where('transaction_type', Tranx::SWAP)
                        ->where('transaction_id', $swap->id)
                        ->update(['status' => Status::FAILED]);

                    rescue(fn () => $swap->user->notify(new SwapStateNotification($swap)));

                    $expiredCount++;
                }
            });

        $this->info("Expired {$expiredCount} stale swap(s).");
        return self::SUCCESS;
    }
}