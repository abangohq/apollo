<?php

namespace App\Nova\Actions;

use App\Mail\TradeRejected;
use App\Models\RejectionReason;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\TradeTransaction;
use App\Notifications\SendFirebasePushNotification;
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
use Throwable;

class RejectTrade extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Reject Trade';

    public $confirmText = "Are you sure you want to reject this trade?";
    public $confirmButtonText = 'Yes';
    public $cancelButtonText = 'No';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        /** @var \App\Models\Trade $trade */
        foreach ($models as $trade) {
            if (! $trade->isPending()) {
                return Action::danger('An action has already been taken on this trade');
            }

            /** @var bool $completed */
            try {
                $completed = DB::transaction(function () use ($trade, $fields) {
                    // * Obtain a query lock on the trade record to prevent update by another process.
                    // * We need something like this to prevent accidental approval while another admin
                    // * is trying to bulk-update the trade or while the subcategory rate is being updated by another process.
                    $tradeRecord = Trade::query()
                        ->where(['id' => $trade->id, 'status' => 'pending'])
                        ->select(['id', 'giftcard_id', 'rate', 'status', 'amount', 'currency'])
                        ->lockForUpdate()
                        ->first();

                    if (! $tradeRecord) {
                        return false;
                    }

                    $trade->update([
                        'status' => 'rejected',
                    ]);

                    // Update the existing transaction to failed status
                    $transaction = Transaction::find($trade->transaction_id);
                    $transaction->update([
                        'status' => 'rejected',
                    ]);

                    // Create TradeTransaction record to link trade and transaction
                    TradeTransaction::create([
                        'trade_id' => $trade->id,
                        'transaction_id' => $transaction->id,
                    ]);

                    $trade->approvals()->create([
                        'assigned_to' => Auth::id(),
                        'status' => 'rejected',
                        'remark' => $fields->remark,
                    ]);

                    return true;
                }, attempts: 3);

                if (! $completed) {
                    return Action::danger('Trade has already been rejected or an error occurred. Please try again.');
                }
            } catch (Throwable $e) {
                report($e);
                return Action::danger('An error occurred while processing the trade. Please try again.');
            }

            // * Move mail and notification sending out of DB transaction.
            // * Notification sending is not on the critical part so we shouldn't let it
            // * derail the DB transaction or overall completion of this action.
            rescue(function () use ($trade, $fields) {
                Mail::to($trade->user)->queue(new TradeRejected($trade, $fields->remark));

                $trade->user?->notify(
                    new SendFirebasePushNotification(
                        'Trade Rejected',
                        "Your trade with reference id {$trade->id} has been rejected"
                    )
                );
            }, rescue: null, report: true);
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
            Select::make('Remark', 'remark')->options(RejectionReason::where('type', 'trade')->pluck('reason', 'reason'))
                ->displayUsingLabels()
                ->searchable(),
        ];
    }
}
