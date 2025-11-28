<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Eminiarts\Tabs\Traits\HasTabs;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Kyc extends Resource
{
    use HasTabs;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Kyc>
     */
    public static $model = \App\Models\Kyc::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'verification_type';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'verification_type', 'verification_value','user_id'
    ];

    public static $with = ['user'];

    public static function authorizedToCreate(Request $request)
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

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    public function authorizeToUpdate(Request $request)
    {
        return false;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable()->hideFromIndex(),

            BelongsTo::make('Username', 'user', User::class)
                ->readonly()
                ->displayUsing(fn ($user) => $user->username),

            Text::make('Verification Type', 'verification_type')
                ->sortable(),

            Text::make('Reference')
                ->sortable()->copyable(),

            Badge::make('Status', fn () => $this->status)
                ->map([
                    'completed' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    'abandoned' => 'danger',
                ]),

            DateTime::make('Date', 'created_at')->readonly()->displayUsing(fn ($d) => $d->format('F j Y h:i A')),
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
