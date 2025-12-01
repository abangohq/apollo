<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Vyuldashev\NovaMoneyField\Money;

/**
 * @property mixed $bank_logo
 */
class Deposit extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CryptoTransaction>
     */
    public static $model = \App\Models\CryptoTransaction::class;

    public static $with = [
        'user',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        return parent::indexQuery($request, $query);

    }

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
        'user_id',
        'type',
        'reference',
    ];

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

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

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            BelongsTo::make('User', 'user', User::class)
                ->displayUsing(fn () => $this->resource?->user?->username)
                ->readonly(),
            Money::make('Payout Amount', 'NGN', 'payout_amount')
                ->locale('en')
                ->readonly()
                ->sortable(),
            Text::make('Crypto')
                ->displayUsing(fn () => $this->resource?->crypto)
                ->readonly(),
            Money::make('Amount', 'USD')
                ->displayUsing(fn () => $this->resource?->usd_value)
                ->readonly(),
            Money::make('Rate', 'NGN')
                ->displayUsing(fn () => $this->resource?->conversion_rate)
                ->readonly(),
            Badge::make('Status', fn () => $this->status)
                ->map([
                    'successful' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                ])
                ->readonly(),
            Text::make('Reference', 'reference')->copyable()->readonly(),
            Text::make('WalletTransaction Hash', 'transaction_hash')
                ->displayUsing(fn () => $this->resource?->transaction_hash)
                ->readonly()->hideFromIndex(),
            Text::make('Platform', 'platform')
            ->displayUsing(fn () => $this->resource?->platform)
            ->readonly(),
            Text::make('Address', 'address')
            ->displayUsing(fn () => $this->resource?->address)
            ->readonly(),
            Stack::make('Created At', [
                DateTime::make('Date', 'created_at')->readonly()->displayUsing(function ($value) {
                    return $value->format('F j, Y');
                }),
                DateTime::make('Time', 'created_at')->readonly()->displayUsing(function ($value) {
                    return $value->format('h:i A');
                }),
            ])->showOnPreview(),

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
