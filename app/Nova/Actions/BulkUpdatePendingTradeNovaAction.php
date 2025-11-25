<?php

namespace App\Nova\Actions;

use App\Models\Trade;
use App\Nova\Subcategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Throwable;
use Vyuldashev\NovaMoneyField\Money;

class BulkUpdatePendingTradeNovaAction extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = "Update Pending Trades (Bulk)";

    public $confirmButtonText = "Update";
    public $confirmHeading = "Update Trades";

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection<int, \App\Models\Trade>  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if ($models->isEmpty()) {
            return Action::danger('No trades selected for update.');
        }

        $viableTrades = $models->reject(fn ($trade) => $trade->status !== 'pending');
        if ($viableTrades->isEmpty()) {
            return Action::danger('No pending trades selected for update.');
        }

        // * Ensure at least one field was updated.
        $updateFields = collect([
            'giftcard_id' => $fields->giftcard->id ?? null,
            'rate' => $fields->rate ?? null,
            'amount' => $fields->amount ?? null,
        ])->reject(fn ($value) => empty($value));
        if ($updateFields->isEmpty()) {
            throw ValidationException::withMessages([
                'giftcard' => 'At least one field must be updated.',
            ]);
        }

        try {
            $this->updateAllAtOnce($viableTrades, $updateFields, $fields);
            $updatedTradeIDs = $viableTrades->pluck('id')->toArray();

            // $updatedTradeIDs = $this->updateIndividually($viableTrades, $updateFields);
        } catch (Throwable $e) {
            report($e);
            return Action::danger("An error occurred while updating trades: {$e->getMessage()}");
        }

        activity()
            ->withProperties([
                'action' => 'Bulk Trade Update',
                'fields' => $updateFields,
                'trade_ids' => $viableTrades->pluck('id'),
                'updated_trade_ids' => $updatedTradeIDs,
                'comments' => $fields->comments ?? null,
            ])
            ->log("Bulk updated ".count($updatedTradeIDs)." trades.");

        return Action::message(count($updatedTradeIDs)." PENDING trades updated successfully.");
    }

    private function updateAllAtOnce(Collection $viableTrades, Collection $updateFields)
    {
        DB::transaction(function () use ($viableTrades, $updateFields) {
            /** @var \Illuminate\Database\Eloquent\Collection $pendingTrades */
            $pendingTrades = Trade::query()
                ->pending()
                ->whereIn('id', $viableTrades->pluck('id'))
                ->select(['id', 'giftcard_id', 'rate', 'status', 'amount'])
                ->lockForUpdate()
                ->get();
            if ($pendingTrades->isEmpty()) {
                return;
            }

            // * Update the rate of those pending trades with the new rate
            $pendingTrades->toQuery()->update($updateFields->toArray());
        }, attempts: 3);
    }

    private function updateIndividually(Collection $viableTrades, Collection $updateFields): array
    {
        $updatedTradeIDs = [];
        $viableTrades
            ->each(function ($trade) use ($updateFields, &$updatedTradeIDs) {
                DB::transaction(function () use ($trade, $updateFields, &$updatedTradeIDs) {
                    $tradeRecord = Trade::query()
                        ->where(['id' => $trade->id, 'status' => 'pending'])
                        ->select(['id', 'giftcard_id', 'rate', 'status', 'amount'])
                        ->lockForUpdate()
                        ->first();

                    // * If the trade is not found, move on to the next one
                    if (! $tradeRecord) {
                        return true;
                    }

                    $tradeRecord->update($updateFields->toArray());
                    $updatedTradeIDs[] = $tradeRecord->id;
                }, attempts: 3);
            });

        return $updatedTradeIDs;
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Heading::make("You selected {$request->selectedResources()->count()} records. Only PENDING trades will be updated."),
            Heading::make('Enter the values you want to update. At least one field is required.'),
            Heading::make("<h2 class='text-red-500 font-bold'>NOTE: All the selected trades will be updated with the values you provide here.</h2>")
                ->asHtml(),

            BelongsTo::make('Giftcard', 'giftcard', Subcategory::class)
                ->withoutTrashed()
                // ->searchable()
                ->nullable(),

            Money::make('Rate', 'NGN')
                ->locale('en')
                ->storedInMinorUnits()
                ->nullable(),

            Money::make('Amount', 'USD')
                ->showOnPreview()
                ->locale('en')
                ->storedInMinorUnits()
                ->nullable(),

            Textarea::make('Comments (optional)', 'comments')
                ->rules(['nullable', 'string', 'max:255'])
                ->help("Optional comment explaining the reason for the update."),
        ];
    }
}
