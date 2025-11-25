<?php

namespace App\Jobs;

use App\Models\Trade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * This job updates the rate of pending trades for a specific gift card ID.
 * This job is dispatched when a new sub-category rate is set for a gift card.
 * It ensures that all pending trades with the specified gift card ID are updated with the new rate.
 * It acquires a database lock on the pending trades to ensure atomicity during the update.
 */
class UpdatePendingTradesWithNewSubCategoryRateJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 120;

    /**
     * Create a new job instance.
     * @param array{giftCardID: string, newRate: float} $updateInfo
     */
    public function __construct(private readonly array $updateInfo)
    {
        //
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->updateInfo['giftCardID'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info("Starting pending trades giftcard rate update job...", $this->updateInfo);
        // * Get all pending trades with the given gift card ID
        // * Acquire a db lock on those pending records
        // * Update the rate of those pending trades with the new rate
        if (! Trade::pending()->where('giftcard_id', $this->updateInfo['giftCardID'])->exists()) {
            info("No pending trades found for the given gift card ID {$this->updateInfo['giftCardID']}. Exiting...");
            return;
        }

        try {
            DB::transaction(function () {
                /** @var \Illuminate\Database\Eloquent\Collection $pendingTrades */
                $pendingTrades = Trade::query()
                    ->pending()
                    ->where('giftcard_id', $this->updateInfo['giftCardID'])
                    ->select(['id', 'rate', 'status'])
                    ->lockForUpdate()
                    ->get();

                info("Found {$pendingTrades->count()} pending trades. Updating...");

                $pendingTrades->toQuery()->update([
                    'rate' => $this->updateInfo['newRate'],
                ]);

                info("Updated {$pendingTrades->count()} pending trades with the new rate of {$this->updateInfo['newRate']}.");
            }, attempts: 2);
        } catch (Throwable $e) {
            report($e);
            info("Error updating pending trades giftcard rate: {$e->getMessage()}");
            return;
        }

        info("Finished pending trades giftcard rate update job.");
    }
}
