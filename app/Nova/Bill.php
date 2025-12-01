<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Vyuldashev\NovaMoneyField\Money;

/**
 * @property mixed $bank_logo
 */
class Bill extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\WalletTransaction>
     */
    public static $model = \App\Models\WalletTransaction::class;

    public static $with = [
        'user:id,username',
        'transactable'
    ];

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
        'transaction_type',
        'reference',
        'narration',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = parent::indexQuery($request, $query);
        $types = ['airtime', 'betting', 'cable', 'data', 'meter', 'wifi'];
        return $query->whereIn('transaction_type', $types);
    }

    public function authorizedToUpdate(Request $request)
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

    public function authorizedToDelete(Request $request)
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
            Money::make('Amount', 'NGN', 'amount')
                ->locale('en')
                ->readonly(fn () => $this->status !== 'pending')
                ->sortable(),
            Text::make('Type', fn () => $this->transaction_type)->readonly(),
            // Money::make('Charge', 'NGN', 'charge')
            //     ->locale('en')
            //     ->readonly(fn () => $this->status !== 'pending')
            //     ->sortable(),
            Badge::make('Status', fn () => $this->status)
                ->map([
                    'successful' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                ])
                ->readonly(),
            Text::make('reference')->copyable()->readonly(),
            Stack::make('Created At', [
                DateTime::make('Date', 'created_at')->readonly()->displayUsing(function ($value) {
                    return $value->format('F j, Y');
                }),
                DateTime::make('Time', 'created_at')->readonly()->displayUsing(function ($value) {
                    return $value->format('h:i A');
                }),
            ])->showOnPreview(),

            $this->transactableDetailsPanel(),
        ];
    }

    /**
     * Generate a panel to display details of the transactable model.
     *
     * @return \Laravel\Nova\Panel
     */
    protected function transactableDetailsPanel()
    {
        return Panel::make('Transactable Details', function () {
            $transactable = $this->transactable;

            if ($transactable) {
                // Display fields specific to the transactable type
                return [
                    Text::make('Type', fn() => class_basename($transactable))->onlyOnDetail(),
                    Text::make('Product', fn() => $transactable->product ?? 'N/A')->onlyOnDetail(),
                    Text::make('Customer ID', fn() => $transactable->customer_id ?? 'N/A')->onlyOnDetail(),
                    Text::make('Phone', fn() => $transactable->phone_no ?? 'N/A')->onlyOnDetail(),
                    Text::make('Amount Requested', fn() => $transactable->amount_requested ?? 'N/A')->onlyOnDetail(),
                    Text::make('Amount Paid', fn() => $transactable->amount_paid ?? 'N/A')->onlyOnDetail(),
                    Text::make('Smart Card', fn() => $transactable->smart_card_no ?? 'N/A')->onlyOnDetail(),
                    Text::make('Meter Token', fn() => $transactable->token ?? 'N/A')->onlyOnDetail(),
                ];
            }

            return [];
        });
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
            new Filters\TransactionType,
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
