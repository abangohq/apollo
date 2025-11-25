<?php

namespace App\Actions\Finance;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Admin\Finance\CreateRecRequest;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\User\ReconcileNotification;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;

class ReconcileAction
{
   use WalletEntity, Conditionable;

   public function __construct(public CreateRecRequest $request)
   {
      //
   }

   /**
    * Handle the action
    * @see \App\Traits\WalletEntity
    * @return \App\Models\WalletTransaction
    */
   public function handle()
   {
      try {
         DB::beginTransaction();
         $entry = $this->request->entry;
         $user = User::findOrFail($this->request->user_id);

         $this->{$entry}($this->request->user_id, $this->request->amount);

         $reconcile = Reconciliation::create($this->reconcilePayload());
         WalletTransaction::create($this->walletTransaction($reconcile));

         rescue(fn() => $user->notify(new ReconcileNotification($reconcile)));
         DB::commit();

         $reconcile->load(['transaction', 'originTransaction']);

         return $reconcile;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // throw for handler
      }
   }

   /**
    * Reconciliation payload
    */
   public function reconcilePayload()
   {
      return [
         'reference' => Utils::generateReference(Prefix::RECONCILE),
         'user_id' => $this->request->user_id,
         'staff_id' => auth()->id(),
         'origin_tranx_id' => $this->request->transaction_id,
         'entry' => $this->request->entry,
         'amount' => $this->request->amount,
         'status' => Status::SUCCESSFUL,
         'reason' => $this->request->reason
      ];
   }

   /**
    * Create the BettingTopUp payload
    */
   public function walletTransaction(Reconciliation $reconciliation, $charge = 0)
   {
      return [
         'user_id' => $this->request->user_id,
         'reference' => $reconciliation->reference,
         'transaction_type' => Tranx::RECONCILE,
         'transaction_id' => $reconciliation->id,
         'entry' => $this->request->entry,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => $this->request->narration,
         'amount' => $reconciliation->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $reconciliation->amount,
         'wallet_balance' => User::query()->findOrfail($this->request->user_id)->wallet->balance
      ];
   }
}
