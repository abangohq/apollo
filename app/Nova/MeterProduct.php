<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @property \App\Models\MeterProduct $resource
 * @property mixed $status
 */
class MeterProduct extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\MeterProduct>
     */
    public static $model = \App\Models\MeterProduct::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
    ];

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

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Image::make('Logo')->thumbnail(fn () => $this->logo)
                ->thumbnail(fn ($value) => $value)
                ->preview(fn ($value) => $value)
                ->rounded()
                ->disableDownload(),

            Text::make('Name')
                ->showOnPreview()
                ->readonly(),

            Boolean::make('Status')
                ->trueValue(true)
                ->falseValue(false),

        ];
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
        return [];
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

        ];
    }
}
