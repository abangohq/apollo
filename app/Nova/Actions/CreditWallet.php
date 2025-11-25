<?php

namespace App\Nova\Actions;

use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vyuldashev\NovaMoneyField\Money;

class CreditWallet extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Credit Wallet';

    public function authorizedToRun(Request $request, $model)
    {
        return $request->user()->type === 'super_admin' ?? false;
    }

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $user) {
            (new WalletService())->credit(
                $user,
                'NGN',
                $fields->get('amount') * 100,
                'reconcile',
            );

            return Action::message("$user->name has been credited!");

        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Money::make('Amount', 'NGN'),
        ];
    }
}
