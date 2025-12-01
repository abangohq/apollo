<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vyuldashev\NovaMoneyField\Money;

/**
 * @property \App\Models\CryptoRate $resource
 * @property mixed $status
 */
class CryptoRate extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CryptoRate>
     */
    public static $model = \App\Models\CryptoRate::class;

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
        'rate_range',
    ];

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

            Text::make('Rate Range')
                ->showOnPreview(),

            Number::make('Range Start')
                ->showOnPreview(),

            Number::make('Range End')
                ->showOnPreview(),

            Money::make('Rate', 'NGN')
                ->showOnPreview()
                ->locale('en')
                ->storedInMinorUnits(),

            Number::make('Fee (%)', 'fee')
                ->showOnPreview(),

            Boolean::make('Published', 'is_published')
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
