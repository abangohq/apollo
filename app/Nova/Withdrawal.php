<?php

namespace App\Nova;

use App\Nova\Actions\RejectWithdrawal;
use App\Nova\Filters\MetricDateRangeFilter;
use App\Nova\Filters\StatusFilter;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vyuldashev\NovaMoneyField\Money;

class Withdrawal extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Withdrawal>
     */
    public static $model = \App\Models\Withdrawal::class;

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
        'reference',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        return parent::indexQuery($request, $query);
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
            BelongsTo::make('Username', 'user', User::class)
                ->readonly()
                ->displayUsing(fn ($user) => $user->username),
            Money::make('Amount', 'NGN')
                ->locale('en')
                ->readonly()
                ->sortable(),
            // Text::make('Balance Before', function () {
            //     $prev = optional(optional($this->transaction)->history)->previous_balance;
            //     return $prev !== null ? number_format($prev, 2) : 'N/A';
            // })->readonly()->sortable(),
            // Text::make('Balance After', function () {
            //     $curr = optional(optional($this->transaction)->history)->current_balance;
            //     return $curr !== null ? number_format($curr, 2) : 'N/A';
            // })->readonly()->sortable(),
            Stack::make('Bank Details', [
                Text::make('Bank Name')->readonly(),
                Text::make('Account Name')->readonly(),
            ]),

            Badge::make('Status', fn () => $this->status)
                ->map([
                    'successful' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    'rejected' => 'danger',
                ])
                 ->readonly(),
            Text::make('reference')->copyable()->readonly(),
            DateTime::make('Created At')->readonly()->displayUsing(fn ($d) => $d->format('F j Y h:i A')),
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
            new StatusFilter,
            new \Marshmallow\Filters\DateRangeFilter('created_at', 'Created date'),
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
            // (new RejectWithdrawal)
            //     ->showInline()
            //     ->confirmText('Are you sure you want to reject this withdrawal?')
            //     ->confirmButtonText('Yes')
            //     ->cancelButtonText('No'),
        ];
    }
}
