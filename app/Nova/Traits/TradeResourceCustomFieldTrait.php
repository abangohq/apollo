<?php

namespace App\Nova\Traits;

use App\Nova\Fields\Files;
use Illuminate\Support\Facades\App;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/** @property \App\Models\Trade $resource */
trait TradeResourceCustomFieldTrait
{
    protected function getDuplicateImagesDetectedBadgeField(NovaRequest $request): Text
    {
        return Text::make('')
            ->exceptOnForms()
            ->canSee(fn () => $this->resource->hasDuplicateImages() and $this->resource->isPending())
            ->displayUsing(function () use ($request) {
                if (! $this->resource->hasDuplicateImages() or ! $this->resource->isPending()) {
                    return null;
                }

                // ? We are not selecting the duplicate_images_info on indexQuery to ensure the json
                // ? column content doesn't slow down the query result on the index page.
                // ? Instead we will do a check for the duplicate_images_info presence and content count directly
                // ? from the sql query.
                $duplicateImagesCount = $request->isResourceIndexRequest()
                    ? $this->resource->duplicate_images_count
                    : count($this->resource->duplicate_images_info ?? []);

                $title = $request->isResourceIndexRequest()
                    ? "{$duplicateImagesCount} ".str("DUPLICATE")->plural($duplicateImagesCount)." DETECTED"
                    : "{$duplicateImagesCount} POSSIBLE ".str("DUPLICATE")->plural($duplicateImagesCount)." IMAGES DETECTED";
                $titleAttrTooltip = $request->isResourceIndexRequest()
                    ? "{$duplicateImagesCount} possible duplicate image(s) detected. More info on details page."
                    : "";
                $badge = ($this->resource->hasDuplicateImages())
                    ? "<span title='{$titleAttrTooltip}' class='inline-flex items-center whitespace-nowrap min-h-6 px-2 mt-3 rounded uppercase text-xs font-bold bg-red-500 text-white dark:bg-red-500 dark:text-white _dark:text-red-900 mb-1' style='cursor: pointer;'>{$title}</span>"
                    : '';

                if ($request->isResourceIndexRequest()) {
                    return $badge;
                }

                $trades = \App\Models\Trade::query()
                    ->whereIn(
                        'id',
                        collect($this->resource->duplicate_images_info)->pluck('matchedTradeID')->unique()->values()
                    )
                    ->orderBy('created_at', 'desc')
                    ->orderBy('status', 'desc')
                    ->get(['id', 'status', 'created_at']);

                $groupedDuplicatedImages = collect($this->resource->duplicate_images_info)
                    ->sortBy('matchedTradeID')
                    ->groupBy('matchedTradeID');

                $listHtml = "<h3 class='text-lg font-bold'>Matched Trades ({$trades->count()}) <small>(click trade id to open trade.)</small></h3><ul class='list-disc list-inside'>";

                $trades->each(function ($trade, $index) use (&$listHtml, $groupedDuplicatedImages) {
                    $tradeURL = route('nova.pages.detail', ['resource' => \App\Nova\Trade::uriKey(), 'resourceId' => $trade->id]);
                    $listHtml .= "<li class='p-1'><a href='{$tradeURL}' target='_blank' class='link-default text-base'>".($index + 1).". Trade ID: {$trade->id} - Status: ".strtoupper($trade->status)." - Created At: {$trade->created_at->format('F j, Y')}</a><ul>";
                    $groupedDuplicatedImages
                        ->get($trade->id)
                        ->each(function ($duplicateImage, $index) use (&$listHtml) {
                            $imageIDSubStr = substr($duplicateImage['matchedImageID'], -12);
                            $imageHashSubStr = substr($duplicateImage['matchedImageHash'], -12);
                            $listHtml .= "<li class='p-1'>".($index + 1).". Image ID: <strong>{$imageIDSubStr}</strong> (Hash: <strong>{$imageHashSubStr}</strong>)</li>";
                        });
                    $listHtml .= '</ul>';
                });

                $listHtml .= '</ul>';

                return $badge . $listHtml;
            })
            ->asHtml();
    }

    protected function getDuplicateImagesAttachmentsField(): Files
    {
        return Files::make('Duplicate Images', function () {
            return collect($this->resource->duplicate_images_info ?? [])
                ->sortBy('matchedTradeID')
                ->map(fn ($duplicateImage) => [
                    'id' => $duplicateImage['matchedImageID'],
                    'label' => 'Image ID: ' . substr($duplicateImage['matchedImageID'], -12). ' - Trade ID: ' . $duplicateImage['matchedTradeID'],
                    'attachable_id' => $duplicateImage['matchedTradeID'],
                    'attachable_type' => \App\Models\Trade::class,
                    'path_url' => $duplicateImage['matchedImageURL'],
                ]);
        })
        ->onlyOnDetail()
        ->canSee(fn () => $this->resource->hasDuplicateImages() and $this->resource->isPending())
        ->withLogViewHistory(value: true)
        ->maskUnopenedImages(value: true);
    }

    protected function getAttachmentsField(): Files
    {
        return Files::make('Attachments', function () {
            return $this->resource->images->map(fn (\App\Models\TradeImage $image) => [
                'id' => $image->getKey(),
                'label' => ($image->image_hash and $this->resource->hasDuplicateImages())
                    ?  'ID: '.substr($image->getKey(), -12). " (hash: ".substr($image->image_hash, -12).")"
                    : 'ID: '.substr($image->getKey(), -12),
                'attachable_id' => $image->trade_id,
                'attachable_type' => \App\Models\Trade::class,
                'path_url' => $image->image_url,
            ]);
        })
        ->canSee(function (NovaRequest $request) {
            return in_array($request->user()->type, [
                'super_admin', 'admin'
            ]);
        })
        ->showOnIndex()
        ->withLogViewHistory()
        ->maskUnopenedImages();
    }

    protected function getTotalAmountFormPreviewField(): Heading
    {
        return Heading::make('Total Amount Preview')
            ->onlyOnForms()
            ->readonly()
            ->asHtml()
            ->dependsOn(
                ['rate', 'amount'],
                function (Heading $field, NovaRequest $request, FormData $formData) {
                    if (! empty($formData->get('rate')) and ! empty($formData->get('amount'))) {
                        $totalAmount = 'NGN '. number_format($formData->get('amount') * $formData->get('rate'), 2);
                        $field->withMeta([
                            'value' => '<div class="space-y-2 md:flex @md/modal:flex md:flex-row @md/modal:flex-row md:space-y-0 @md/modal:space-y-0 py-5" index="4"><div class="w-full px-6 md:mt-2 @md/modal:mt-2 md:px-8 @md/modal:px-8 md:w-1/5 @md/modal:w-1/5"></div><div class="w-full space-y-2 px-6 md:px-8 @md/modal:px-8 md:w-3/5 @md/modal:w-3/5"><div class="space-y-1"><h3 class="text-2xl text-red-500 font-bold">'.$totalAmount.'</h3><div class="text-lg font-bold">Total Amount (Preview)</div><div class="text-red-500 font-bold text-base">🚨 Please pay attention to the final amount before submitting your update.🚨</div></div></div></div>',
                        ]);
                    }
                }
            );
    }

    protected function getOpenedBadgeField(): Text
    {
        return Text::make('')
            ->displayUsing(function () {
                return ($this->resource->isPending() and $this->resource->opened_at)
                    ? "<span class='inline-flex items-center whitespace-nowrap min-h-6 px-2 rounded uppercase text-xs font-bold bg-red-500 text-white dark:bg-red-500 dark:text-white _dark:text-red-900 mb-1' style='cursor: pointer;'>OPENED</span>"
                    : '';
            })
            ->asHtml();
    }
}
