<?php

namespace App\Actions\Payment;

use App\Enums\Tranx;
use App\Jobs\User\ProcessWithdraw;
use App\Models\RejectionReason;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\User\WithdrawRejectNotification;
use App\Notifications\User\WithdrawReversalNotification;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\ValidationException;

class DeclineWithdrawAction
{
   use Conditionable, WalletEntity;

   public function __construct()
   {
      request()->validate(['reason' => ['required']]);
   }

   /**
    * Handle the action, and parallel lock on the same
    */
   public function handle()
   {
      $request = request();

      $withdrawal = Withdrawal::query()->findOrFail($request->withdrawal);
      $reason = RejectionReason::find($request['reason']);


      DB::beginTransaction();
      try {
         $this->credit($withdrawal->user_id, $withdrawal->amount);

         $withdrawal->update([
            'status' => Tranx::TRANX_REJECTED,
            'rejection_id' => @$request['reason'],
            'settled_by' => auth()->id()
         ]);
         $tranxPayload = $this->reversalTranxPayload($withdrawal);

         WalletTransaction::create($tranxPayload);

         $withdrawal->user->notify(new WithdrawReversalNotification($withdrawal));
         $withdrawal->user->notify(new WithdrawRejectNotification($reason, $withdrawal));

         DB::commit();

         return $withdrawal;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for global handle
      }
   }

   /**
    * withdrawal action validation
    * 
    * @throws Illuminate\Validation\ValidationException
    */
   public function validation(Withdrawal $withdrawal)
   {
      if ($withdrawal->channel != Tranx::MANUAL->value) {
         throw ValidationException::withMessages([
            'withdrawal' => ['You cant process an automated transaction manually.']
         ]);
      }

      if ($withdrawal->status != Tranx::TRANX_PENDING->value) {
         throw ValidationException::withMessages([
            'withdrawal' => ['Withdrawal has already been processed.']
         ]);
      }

      if (!is_null($withdrawal->settled_by)) {
         throw ValidationException::withMessages([
            'withdrawal' => ['withdrawal has already been initiated.']
         ]);
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(Withdrawal $withdrawal, $charge = 0)
   {
      return [
         'user_id' => $withdrawal->user_id,
         'reference' => "RVSL-{$withdrawal->reference}",
         'transaction_type' => Tranx::WITHDRAW,
         'transaction_id' => $withdrawal->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl of N{$withdrawal->amount} withdrawal request",
         'amount' => $withdrawal->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $withdrawal->amount,
         'wallet_balance' => $withdrawal->user->wallet()->value('balance'),
         'is_reversal' => true,
      ];
   }
}
