<?php

namespace App\Nova;

use App\Nova\Actions\BanUser;
use App\Nova\Actions\CreditWallet;
use App\Nova\Actions\DebitWallet;
use App\Nova\Actions\SendPushNotification;
use App\Nova\Actions\UnbanUser;
use Eminiarts\Tabs\Tab;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\Traits\HasTabs;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Vyuldashev\NovaMoneyField\Money;

class User extends Resource
{
    use HasTabs;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

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
        'id', 'name', 'email','username', 'phone',
    ];

    public static $with = ['wallets'];

    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = parent::indexQuery($request, $query);

        return $query->where('user_type', '=', 'user');
    }
    public static function detailQuery(NovaRequest $request, $query)
    {
        $query = parent::indexQuery($request, $query);

        return $query->where('user_type', '=', 'user');
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

            Gravatar::make()->maxWidth(50),

            Stack::make('User', [
                Text::make('Name')
                    ->sortable()
                    ->rules('required', 'max:255'),

                Text::make('Email')
                    ->sortable()->copyable()
                    ->rules('required', 'email', 'max:254')
                    ->creationRules('unique:users,email')
                    ->updateRules('unique:users,email,{{resourceId}}'),
            ]),

            Text::make('Username')
                ->sortable()->copyable()
                ->rules('required', 'max:255'),

            Text::make('Phone')
                ->sortable()
                ->rules('required', 'max:11'),


            Boolean::make('Email Verified', function () {
                return $this->email_verified_at !== null;
            })->onlyOnIndex(),

            Badge::make('Status', fn () => $this->status === 'active' ? 'Active' : 'Inactive')
                ->map([
                    'Active' => 'success',
                    'Inactive' => 'danger',
                ]),


            DateTime::make('Date Joined', 'created_at')->readonly()->displayUsing(fn ($d) => $d->format('F j Y h:i A')),

            // DateTime::make('Last Traded', function () {
            //     return $this->last_traded;
            // })->readonly()->displayUsing(fn ($d) => $d?->format('F j Y h:i A')),

            // Text::make('total_trades')
            //     ->sortable()->readonly(),

            Money::make('Balance', 'NGN', 'wallet_balance')
                ->sortable()
                ->readonly(),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', Rules\Password::defaults())
                ->updateRules('nullable', Rules\Password::defaults()),

            Tabs::make('Details', [
                Tab::make('Trades', [
                    HasMany::make('Trades', 'trades', Trade::class),
                ]),
                Tab::make('Withdrawals', [
                    HasMany::make('Withdrawals', 'withdrawals'),
                ]),
                // Tab::make('Crypto Transactions', [
                //     HasMany::make('Transaction', 'crypto_transactions', Deposit::class),
                // ]),
                // Tab::make('Bills', [
                //     HasMany::make('Bills', 'bills', Transaction::class),
                // ]),
                Tab::make('Virtual Wallet', [
                    Text::make('Bank Name')->displayUsing(fn () => $this->resource?->virtual_wallet?->bank_name)
                        ->onlyOnDetail(),
                    Text::make('Account Name')->displayUsing(fn () => $this->resource?->virtual_wallet?->account_name)
                    ->onlyOnDetail(),
                    Text::make('Account Number')->displayUsing(fn () => $this->resource?->virtual_wallet?->account_number)
                    ->onlyOnDetail(),
                ]),
            ]),
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
            // (new BanUser())->confirmText('Are you sure you want to ban this user?')
            //     ->confirmButtonText('Yes')
            //     ->cancelButtonText('No'),
            // (new UnbanUser)->confirmText('Are you sure you want to unban this user?')
            //     ->confirmButtonText('Yes')
            //     ->cancelButtonText('No'),
            // (new CreditWallet)->showInline()
            //     ->confirmText('Are you sure you want to credit this user?')
            //     ->confirmButtonText('Yes')
            //     ->cancelButtonText('No')
            //     ->canSee(fn () => $request->user()->type === 'super_admin' ?? false),
            // (new DebitWallet)
            //     ->showInline()
            //     ->confirmText('Are you sure you want to debit this user?')
            //     ->confirmButtonText('Yes')
            //     ->cancelButtonText('No'),
        ];
    }
}
