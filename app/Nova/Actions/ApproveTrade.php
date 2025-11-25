<?php

namespace App\Nova\Actions;

use App\Mail\TradeSuccessful;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\TradeTransaction;
use App\Notifications\SendFirebasePushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Throwable;

class ApproveTrade extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Approve Trade';

    public $confirmText = "Are you sure you want to approve this trade?";
    public $confirmButtonText = 'Yes';
    public $cancelButtonText = 'No';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection<int, \App\Models\Trade>  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        /** @var \App\Models\Trade $trade */
        foreach ($models as $trade) {
            if (! $trade->isPending()) {
                return Action::danger('An action has already been taken on this trade');
            }

            $totalAmount = ($trade->amount * $trade->rate) / 100;

            /** @var \App\Models\Transaction|null $transaction */
            try {
                $transaction = DB::transaction(function () use ($fields, $trade, $totalAmount) {
                    // * Obtain a query lock on the trade record to prevent update by another process.
                    // * We need something like this to prevent accidental approval while another admin
                    // * is trying to bulk-update the trade or while the subcategory rate is being updated by another process.
                    /** @var \App\Models\Trade|null $tradeRecord */
                    $tradeRecord = Trade::query()
                        ->where(['id' => $trade->id, 'status' => 'pending'])
                        ->select(['id', 'giftcard_id', 'rate', 'status', 'amount', 'currency'])
                        ->lockForUpdate()
                        ->first();

                    if (! $tradeRecord) {
                        Log::info("Pending trade record not found for trade ID: {$trade->id}");
                        return null;
                    }

                    $tradeRecord->update([
                        'status' => 'approved',
                    ]);

                    // Update the existing transaction instead of creating a new one
                    $transaction = Transaction::find($trade->transaction_id);
                    $transaction->update([
                        'status' => 'successful',
                        'amount' => $totalAmount,
                    ]);

                    // Create TradeTransaction record to link trade and transaction
                    TradeTransaction::create([
                        'trade_id' => $trade->id,
                        'transaction_id' => $transaction->id,
                    ]);

                    $trade->approvals()->create([
                        'assigned_to' => Auth::id(),
                        'status' => 'approved',
                        'remark' => $fields->remark,
                    ]);

                    $trade->user->wallets()->where('currency', 'NGN')->increment('amount', $totalAmount);

                    return $transaction;
                }, attempts: 3);

                // * We will get a null transaction if we fail to get a lock on the pending trade record.
                if (! $transaction) {
                    return Action::danger('Trade has already been approved or an error occurred. Please try again.');
                }
            } catch (Throwable $e) {
                report($e);
                return Action::danger('An error occurred while processing the trade. Please try again.');
            }

            // * Move mail and notification sending out of DB transaction.
            // * Notification sending is not on the critical path so we should not let it
            // * derail the DB transaction or overall completion of this action.
            rescue(function () use ($trade, $transaction) {
                Mail::to($trade->user)->queue(new TradeSuccessful($trade, $transaction));

                $trade->user?->notify(
                    new SendFirebasePushNotification(
                        'Trade Approved',
                        "Your trade with reference id {$trade->id} has been approved."
                    )
                );
            }, rescue: null, report: true);
        }
        return Action::message('Trade approved successfully.');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Remark', 'remark'),
        ];
    }
}
