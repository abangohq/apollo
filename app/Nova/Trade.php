<?php

namespace App\Nova;

use App\Events\NovaTradeResourceDetailPageViewedEvent;
use App\Nova\Actions\ApproveTrade;
use App\Nova\Actions\BulkUpdatePendingTradeNovaAction;
use App\Nova\Actions\RejectTrade;
use App\Nova\Filters\TradeDuplicateImageStatusFilter;
use App\Nova\Traits\TradeResourceCustomFieldTrait;
use Faradele\Files\Files;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vyuldashev\NovaMoneyField\Money;

/**
 * @property \App\Models\Trade $resource
 * @property mixed $status
 */
class Trade extends Resource
{
    use TradeResourceCustomFieldTrait;

    public static $model = \App\Models\Trade::class;

    public static $with = [
        'user:id,username',
        'giftcard.giftcardCategory',
        'images',
        'approvals:id,trade_id,remark',
        'approvals.assignedTo'
    ];

    public static $title = 'id';

    public static $search = [
        'id',
        'e_code',
        'user_id',
    ];

    // * Disable click to open details page on table so that we can intercept the click
    // * and open the trade images in a modal lightbox right there on the index table.
    public static $clickAction = 'ignore';

    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = parent::indexQuery($request, $query)
            ->select([
                'id', 'giftcard_id', 'user_id', 'rate', 'amount',
                'e_code', 'status', 'currency', 'payout_method',
                'opened_at', 'created_at',
                DB::raw("IF(duplicate_images_info IS NOT NULL, 1, 0) AS has_duplicate_images"),
                DB::raw("IF(duplicate_images_info IS NOT NULL, JSON_LENGTH(duplicate_images_info), 0) AS duplicate_images_count"),
            ]);

        return $query;
    }

    public static function detailQuery(NovaRequest $request, $query)
    {
        $resourceId = $request->resourceId;
        if ($resourceId) {
            NovaTradeResourceDetailPageViewedEvent::dispatch($resourceId);
        }
        return parent::detailQuery($request, $query);
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizeToReplicate(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    public function fieldsForIndex(NovaRequest $request): array
    {
        return [
            Stack::make('User', [
                BelongsTo::make('User', 'user', User::class)
                    ->displayUsing(fn () => $this->resource?->user?->username),
                $this->getOpenedBadgeField(),
            ]),

            Stack::make('Giftcard', [
                BelongsTo::make('Giftcard', 'giftcard', Subcategory::class),
                Text::make('Category', fn () =>
                    $this->resource->giftcard?->giftcardCategory?->name ?? 'N/A'
                ),
            ]),

            $this->getStatusBadge(),

            Stack::make('Attachments', [
                $this->getAttachmentsField()
                    ->disablePreviewModal(false),
                $this->getDuplicateImagesDetectedBadgeField($request),
            ]),

            Money::make('Rate', 'NGN')
                ->locale('en')
                ->storedInMinorUnits()
                ->sortable(),

            Money::make('Amount', 'USD')
                ->locale('en')
                ->storedInMinorUnits(),

            Text::make('Total Value')
                ->displayUsing(fn () => '₦'.number_format($this->resource->amount * $this->resource->rate / 10000, 2)),

            Text::make('E Code')->copyable(),

            $this->getCreateAtDateAndTimeField(),
        ];
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        $canShowOnPreview = ! $this->resource->isPending();
        return [
            ID::make()->showOnPreview(callback: $canShowOnPreview),

            $this->getDuplicateImagesDetectedBadgeField($request),

            $this->getDuplicateImagesAttachmentsField(),

            BelongsTo::make('User', 'user', User::class)
                ->showOnPreview(callback: $canShowOnPreview)
                ->onlyOnDetail()
                ->displayUsing(fn () => $this->resource?->user?->username)
                ->readonly(),

            BelongsTo::make('Giftcard', 'giftcard', SubCategory::class)
                ->withoutTrashed()
                ->onlyOnForms(),

            Stack::make('Giftcard', [
                BelongsTo::make('GiftcardCategory', 'giftcard', GiftcardCategory::class),
                Text::make('Category', fn () =>
                    $this->resource->giftcard?->giftcardCategory?->name ?? 'N/A'
                ),
            ])->showOnPreview(callback: $canShowOnPreview),

            $this->getStatusBadge()
                ->showOnPreview(callback: $canShowOnPreview),

            Text::make('Remark')
                ->showOnPreview(callback: $canShowOnPreview)
                ->onlyOnDetail()
                ->displayUsing(fn () => $this->resource?->approvals?->remark ?? null)
                ->readonly(),

            BelongsTo::make('Assigned To', 'approvals', User::class)
                ->displayUsing(fn () => $this->resource->approvals?->assignedTo->name)
                ->onlyOnDetail()
                ->canSee(function (NovaRequest $request) {
                    return in_array($request->user()->type, [
                        'super_admin'
                    ]);
                }),

            // * Replaced Money fields with Currency fields to get rid of decimal places on edit form.
            // * https://abangotechnol-pve1823.slack.com/archives/D0676NJAY05/p1747988785480539?thread_ts=1747601747.014789&cid=D0676NJAY05
            Currency::make('Rate', 'rate')
                ->showOnPreview(callback: $canShowOnPreview)
                ->currency('NGN')
                ->asMinorUnits(),

            Currency::make('Amount', 'amount')
                ->showOnPreview(callback: $canShowOnPreview)
                ->currency('USD')
                ->asMinorUnits(),

            Text::make('Total Value')
                ->showOnPreview(callback: $canShowOnPreview)
                ->onlyOnDetail()
                ->displayUsing(fn () => '₦'.number_format($this->resource->amount * $this->resource->rate / 10000, 2))
                ->readonly(),

            Text::make('E Code')
                ->showOnPreview(callback: $canShowOnPreview)
                ->readonly()
                ->copyable(),

            DateTime::make('Opened At')
                ->canSee(fn () => $this->resource->opened_at),

            $this->getCreateAtDateAndTimeField()
                ->showOnPreview(callback: $canShowOnPreview),

            // * Do not show on preview modal for better access control.
            $this->getAttachmentsField(),

            HasMany::make('Media View Logs')
                ->collapsedByDefault(),
        ];
    }

    public function fieldsForUpdate(NovaRequest $request): array
    {
        return [
            BelongsTo::make('Giftcard', 'giftcard', SubCategory::class)
                ->withoutTrashed(),

            // * Replaced Money fields with Currency fields to get rid of decimal places on edit form.
            // * https://abangotechnol-pve1823.slack.com/archives/D0676NJAY05/p1747988785480539?thread_ts=1747601747.014789&cid=D0676NJAY05
            Currency::make('Rate', 'rate')
                ->rules(['required', 'min:1'])
                ->currency('NGN')
                ->asMinorUnits(),

            Currency::make('Amount', 'amount')
                ->rules(['required', 'min:1'])
                ->currency('USD')
                ->asMinorUnits(),

            $this->getTotalAmountFormPreviewField(),

            Text::make('E Code')
                ->rules(['nullable']),
        ];
    }

    protected function getCreateAtDateAndTimeField(): Stack
    {
        return Stack::make('Created At', [
            DateTime::make('Date', 'created_at')
                ->readonly()
                ->displayUsing(fn ($value) => $value->format('F j, Y')),
            DateTime::make('Time', 'created_at')
                ->readonly()
                ->displayUsing(fn ($value) => $value->format('h:i A')),
        ]);
    }

    /**
     * The existing code for displaying images attachments before
     * the faradele/nova-files-preview-field package was introduced.
     * @deprecated
     */
    protected function getAttachmentsFieldOld()
    {
        return Text::make('Attachments', function () {
            $images = json_decode($this->images, true);
            $thumbnails = '<div class="flex overflow-hidden -space-x-0.5">
            <dt class="sr-only">Commenters</dt>';

            foreach ($images as $image) {
                $thumbnails .= '<dd><img src="'.$image['image_url'].'" alt="Image" class="h-8 w-8 rounded-full bg-gray-50 ring-2 ring-white"><dd>';
            }
            $thumbnails .= ' </div>';

            return $thumbnails;
        })->asHtml();
    }

    protected function getStatusBadge(): Badge
    {
        return Badge::make('Status')
            ->map([
                'approved' => 'success',
                'pending' => 'warning',
                'rejected' => 'danger',
            ]);
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new \Marshmallow\Filters\DateRangeFilter('created_at', 'Created date'),

            TradeDuplicateImageStatusFilter::make(),
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            ApproveTrade::make()
                ->showInline()
                ->canSee(function (NovaRequest $request) {
                    if ($request->isActionRequest()) {
                        return true;
                    }
                    if (! $this->resource->isPending()) {
                        return false;
                    }

                    if ($request->isResourceIndexRequest() and empty($this->resource->e_code)) {
                        return false;
                    }

                    return true;
                }),

            RejectTrade::make()
                ->showInline()
                ->canSee(function (NovaRequest $request) {
                    if ($request->isActionRequest()) {
                        return true;
                    }

                    if (! $this->resource->isPending()) {
                        return false;
                    }

                    if ($request->isResourceIndexRequest() and empty($this->resource->e_code)) {
                        return false;
                    }

                    return true;
                }),

            BulkUpdatePendingTradeNovaAction::make()
                ->onlyOnIndex(),
        ];
    }
}
