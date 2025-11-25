<?php

namespace App\Jobs;

use App\Models\Trade;
use App\Models\TradeImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlagPendingTradesWithDuplicateImagesJob implements ShouldQueue, ShouldBeUnique
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
     */
    public function __construct(
        private readonly string $tradeID
    ) {
        //
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->tradeID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                /** @var \App\Models\Trade $trade */
                $trade = Trade::pending()
                    ->with('images')
                    ->lockForUpdate()
                    ->findOrFail($this->tradeID, [
                        'id', 'status', 'duplicate_images_info',
                    ]);

                // * Collection of the duplicates images for the current trade.
                $matchedDuplicates = collect();
                // * Array to hold information for storing the inverse of the
                // * matches with our trade on the matched trades as well.
                // * This will enable us to show the duplicate image detected flag on both trades
                // * in the Nova UI if the trade hosting the duplicate images is also in the pending state.
                $inverseDuplicates = [];
                $trade->images->each(function (TradeImage $tradeImage) use ($matchedDuplicates, &$inverseDuplicates) {
                    // * Get the duplicates for the current trade image
                    $duplicates = $tradeImage->getDuplicateImages();

                    // * For each of the duplicates, prepare the duplicate array info we want to store on the current trade.
                    $duplicates->each(function (TradeImage $duplicateImage) use ($tradeImage, $matchedDuplicates, &$inverseDuplicates) {
                        $matchedDuplicates->push([
                            'thisImageID' => $tradeImage->id,
                            'matchedImageURL' => $duplicateImage->image_url,
                            'matchedImageID' => $duplicateImage->id,
                            'matchedTradeID' => $duplicateImage->trade_id,
                            'matchedImageHash' => $duplicateImage->image_hash,
                        ]);

                        // * Update the inverse duplicates array as well so that we can update
                        // * the trades hosting the duplicates images as well.
                        $inverseDuplicates[$duplicateImage->trade_id][] = [
                            'thisImageID' => $duplicateImage->id,
                            'matchedImageURL' => $tradeImage->image_url,
                            'matchedImageID' => $tradeImage->id,
                            'matchedTradeID' => $tradeImage->trade_id,
                            'matchedImageHash' => $tradeImage->image_hash,
                        ];
                    });
                });

                // * If there are duplicate images, flag the trade
                if ($matchedDuplicates->isNotEmpty()) {
                    // * Update the trade with the duplicate images info
                    $trade->update([
                        'duplicate_images_info' => $matchedDuplicates->toArray(),
                    ]);

                    // * Update the matched trades with the duplicate images info
                    collect($inverseDuplicates)
                        ->each(function (array $duplicateImages, string $thisTradeID) {
                            // * Only attend to pending trades for now...
                            $matchedTrade = Trade::find($thisTradeID, ['id', 'status', 'duplicate_images_info']);
                            $duplicateImages = collect($duplicateImages)
                                ->unique(fn ($item) => $item['matchedTradeID'].'-'.$item['matchedImageID']);
                            $matchedTrade->update([
                                'duplicate_images_info' => collect($matchedTrade->duplicate_images_info ?? [])
                                    ->merge($duplicateImages)
                                    ->unique(fn ($item) => $item['matchedTradeID'].'-'.$item['matchedImageID'])
                                    ->toArray(),
                            ]);
                        });
                }
            }, attempts: 3);
        } catch (Throwable $e) {
            // * Log the error
            Log::error('Error flagging pending trades with duplicate images: ' . $e->getMessage(), [
                'trade_id' => $this->tradeID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            //
        }
    }
}
