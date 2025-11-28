<?php

namespace App\Nova\Actions;

use App\Mail\TradeRejected;
use App\Mail\WithdrawalRejected;
use App\Models\RejectionReason;
use App\Models\User;
use App\Notifications\SendFirebasePushNotification;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class RejectWithdrawal extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Reject Withdrawal';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $withdrawal) {
            if ($withdrawal->status !== 'pending') {
                return Action::danger('An action has already been taken on this withdrawal');
            }

            DB::transaction(function () use ($withdrawal, $fields) {
                $withdrawal->update([
                    'status' => 'failed',
                ]);

                (new WalletService())->credit(
                    User::where('id', $withdrawal->user_id)->first(),
                    $withdrawal->currency,
                    abs($withdrawal->amount),
                    'reversal',
                    'Admin rejected withdrawal - reversal',
                    $withdrawal->reference
                );
            });

            Mail::to($withdrawal->user)->queue(new WithdrawalRejected($withdrawal->bank, $withdrawal, 'bank transfer failed'));

            $withdrawal->user->notify(
                new SendFirebasePushNotification(
                    'Withdrawal Failed',
                    "Your withdrawal with reference id {$withdrawal->id} has failed"
                )
            );
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
            Select::make('Remark', 'remark')->options(RejectionReason::where('type', 'withdrawal')->pluck('reason', 'reason'))
                ->displayUsingLabels()
                ->searchable(),
        ];
    }
}
