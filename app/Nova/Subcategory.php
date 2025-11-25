<?php

namespace App\Nova;

use App\Nova\Filters\GiftCardCategoryPillFilter;
use DigitalCreative\MegaFilter\MegaFilter;
use DigitalCreative\MegaFilter\MegaFilterTrait;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaSortable\Traits\HasSortableRows;
use Vyuldashev\NovaMoneyField\Money;

/**
 * @property mixed $preview_image
 * @property mixed $logo_image
 */
class Subcategory extends Resource
{
    use HasSortableRows;
    use MegaFilterTrait;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Giftcard>
     */
    public static $model = \App\Models\Giftcard::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'giftcardCategory.name',
    ];

    public function authorizedToView(Request $request)
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

    /**
     * Return the location to redirect the user after creation.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\URL|string
     */
    public static function redirectAfterCreate(NovaRequest $request, $resource)
    {
        return '/resources/'.static::uriKey();
    }

    /**
     * Return the location to redirect the user after update.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @return \Laravel\Nova\URL|string
     */
    public static function redirectAfterUpdate(NovaRequest $request, $resource)
    {
        return '/resources/'.static::uriKey();
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()
                ->sortable()
                ->hideFromIndex(),

            Text::make('Name')
                ->hideFromIndex(),

            BelongsTo::make('Giftcard Category', 'giftcardCategory', GiftcardCategory::class)
                ->hideFromIndex(),

            Image::make('Image')->thumbnail(fn () => $this->image)
                ->rounded()
                ->thumbnail(fn ($value) => $value)
                ->preview(fn ($value) => $value)
                ->store(function (NovaRequest $request, $model) {
                    return [
                        'image' => cloudinary()->upload($request->image->getRealPath(), [
                            'folder' => 'pegasus/giftcards',
                        ])->getSecurePath(),
                    ];
                })
                ->disableDownload()
                ->hideFromIndex(),

            Stack::make('Giftcard', [
                BelongsTo::make('GiftcardCategory', 'giftcardCategory', GiftcardCategory::class)
                    ->sortable(),
                Text::make('Name')->sortable(),
            ]),

            Money::make('Minimum Amount')
                ->storedInMinorUnits()
                ->locale('en')
                ->sortable(),

            Money::make('Maximum Amount')
                ->storedInMinorUnits()
                ->locale('en')
                ->sortable(),

            Money::make('Rate', 'NGN')
                ->storedInMinorUnits()
                ->locale('en')
                ->sortable(),

            Boolean::make('High Rate')
                ->trueValue(true)
                ->falseValue(false),

            Boolean::make('Active')
                ->trueValue(true)
                ->falseValue(false),

            Textarea::make('Terms'),
        ];
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
            MegaFilter::make([
                GiftCardCategoryPillFilter::make()
                    ->wrapMode()
                    ->single(),
            ]),
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
        return [];
    }
}
