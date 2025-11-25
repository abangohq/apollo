<?php

namespace App\Actions\Bills;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Bills\FundBettingRequest;
use App\Jobs\Bills\PurchaseBetting;
use App\Models\BettingTopUp;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Notifications\Admin\FlaggedTransactionNotice;
use App\Support\Utils;
use App\Traits\AdminNotifier;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BettingTopupAction
{
   use WalletEntity, AdminNotifier;

   public function __construct(public FundBettingRequest $request)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $reference = Utils::generateReference(Prefix::BETTING_TOPUP);

      DB::beginTransaction();
      try {
         $this->debit($this->request->user()->id, $this->request->amount);
         $topup = BettingTopUp::create($this->topupPayload($reference));
         $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));

         $flagged = $this->request->user()->requiresManualReview();
         if ($flagged) {
            $this->notifyAdmins($topup, $reference);
         } else {
            PurchaseBetting::dispatch($topup);
         }

         DB::commit();

         return $tranx;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // throw for handler
      }
   }

   /**
    * Create the BettingTopUp payload
    */
   public function topupPayload($reference)
   {
      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->product,
         'customer_id' => $this->request->customer_id,
         'amount' => $this->request->amount,
         'phone_no' => $this->request->phone_no,
         'charge' => 0,
         'profile' => null,
         'reference' => $reference,
         'status' => Status::PENDING,
         'provider_status' => null,
      ];
   }

   /**
    * Get the transaction payload
    */
   public function tranxPayload($reference, BettingTopUp $topup, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $reference,
         'transaction_type' => Tranx::BETTING,
         'transaction_id' => $topup->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$topup->product} N{$topup->amount} bet fund",
         'amount' => $topup->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Notify admin of flagged pending transaction
    */
   protected function notifyAdmins(BettingTopUp $topup, string $reference): void
   {
       $this->notifyFlaggedAdmins('betting', $topup->user, $reference, $topup->amount);
   }
}