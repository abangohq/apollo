<?php

namespace App\Actions\Bills;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Bills\AirtimeTopupRequest;
use App\Jobs\Bills\PurchaseAirtime;
use App\Models\AirtimeTopUp;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Notifications\Admin\FlaggedTransactionNotice;
use App\Support\Utils;
use App\Traits\WalletEntity;
use App\Traits\AdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AirtimeTopupAction
{
   use WalletEntity, AdminNotifier;

   public function __construct(public AirtimeTopupRequest $request)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $reference = Utils::generateReference(Prefix::AIRTIME_TOPUP);

      DB::beginTransaction();
      try {
         $this->debit($this->request->user()->id, $this->request->amount);
         $topup = AirtimeTopUp::create($this->topupPayload($reference));
         $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));

         $flagged = $this->request->user()->requiresManualReview();
         if ($flagged) {
            $this->notifyAdmins($topup, $reference);
         } else {
            PurchaseAirtime::dispatch($topup);
         }

         DB::commit();

         return $tranx;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for handler
      }
   }

   /**
    * Create the AirtimeTopUp payload
    */
   public function topupPayload($reference)
   {
      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->network,
         'phone_no' => $this->request->phone_no,
         'amount_requested' => $this->request->amount,
         'amount_paid' => null,
         'discount_percentage' => null,
         'discount_value' => null,
         'reference' => $reference,
         'status' => Status::PENDING,
         'provider_status' => null,
      ];
   }

   /**
    * Get the transaction payload
    */
   public function tranxPayload($reference, AirtimeTopUp $topup, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $reference,
         'transaction_type' => Tranx::AIRTIME,
         'transaction_id' => $topup->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$topup->product} vtu {$topup->phone_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Notify admin of flagged pending transaction
    */
   protected function notifyAdmins(AirtimeTopUp $topup, string $reference): void
   {
       $this->notifyFlaggedAdmins('airtime', $topup->user, $reference, $topup->amount_requested);
   }
}