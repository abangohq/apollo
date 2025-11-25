<?php

namespace App\Traits;

use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Admin\FlaggedTransactionNotice;

trait WalletEntity
{
   /**
    * Debit user wallet and do a recheck on balance
    *
    * @throws \Symfony\Component\HttpKernel\Exception\HttpException
    */
   public function debit(mixed $userId, int $amount)
   {
      $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

      if ($wallet->balance < $amount) {
         abort(409, 'Your available balance is insufficient to complete this transaction.');
      }

      $updated = (bool) $wallet->update(['balance' => DB::raw("balance - {$amount}")]);

      abort_if(!$updated, 409, 'Error occurred while trying to process transaction.');
   }

   /**
    * Credit user wallet 
    * 
    * @throws \Symfony\Component\HttpKernel\Exception\HttpException
    */
   public function credit(mixed $userId, int $amount)
   {
      $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

      $updated = (bool) $wallet->update(['balance' => DB::raw("balance + {$amount}")]);

      abort_if(!$updated, 409, 'Error occurred while trying to process transaction.');
   }

}
