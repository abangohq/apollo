<?php

namespace App\Actions\Payment;

use App\Enums\Tranx;
use App\Jobs\User\ProcessWithdraw;
use App\Models\Withdrawal;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\ValidationException;

class ApproveWithdrawAction
{
   use Conditionable, WalletEntity;

   public function __construct()
   {
      //
   }

   /**
    * Handle the action, and parallel lock on the same
    */
   public function handle()
   {
      $request = request();

      $withdrawal = Withdrawal::query()->findOrFail($request->withdrawal);

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

      DB::beginTransaction();
      try {
         $withdrawal->update(['settled_by' => auth()->id()]);
         ProcessWithdraw::dispatch($withdrawal);
         DB::commit();

         return $withdrawal;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for global handle
      }
   }
}
