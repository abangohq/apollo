<?php

namespace App\Actions\Bills;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Bills\CableTopupRequest;
use App\Jobs\Bills\PurchaseCablePlan;
use App\Models\CableTopUp;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Notifications\Admin\FlaggedTransactionNotice;
use App\Repositories\BillRepository;
use App\Support\Utils;
use App\Traits\AdminNotifier;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CableTopupAction
{
   use WalletEntity, AdminNotifier;

   public function __construct(public CableTopupRequest $request)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $plan = $this->retrievePlan();
      $reference = Utils::generateReference(Prefix::CABLE_TOPUP);

      DB::beginTransaction();
      try {
         $this->debit($this->request->user()->id, $plan->amount);
         $topup = CableTopUp::create($this->topupPayload($plan, $reference));
         $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));

         $flagged = $this->request->user()->requiresManualReview();
         if ($flagged) {
            $this->notifyAdmins($topup, $reference);
         } else {
            PurchaseCablePlan::dispatch($topup);
         }

         DB::commit();

         return $tranx;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for handler
      }
   }

   /**
    * Retrieve data plan from provider
    * @return object
    */
   public function retrievePlan()
   {
      $plans = app(BillRepository::class)->cablePlans($this->request->product);

      $plan = collect($plans)->first(function ($plan) {
         return $plan->code === $this->request->code;
      });

      if (is_null($plan)) {
         throw ValidationException::withMessages(['code' => ['The selected plan is invalid.']]);
      }

      if ($plan->amount > $this->request->user()->wallet->balance) {
         throw ValidationException::withMessages(['code' => ['Your available balance is insufficient to complete this transaction.']]);
      }

      return $plan;
   }

   /**
    * Create the AirtimeTopUp payload
    */
   public function topupPayload($plan, $reference)
   {
      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->product,
         'code' => $plan->code,
         'name' => $plan->name,
         'smart_card_no' => $this->request->smart_card_no,
         "customer_name" => $this->request->customer_name,
         "phone_no" => null,
         'amount_requested' => $plan->amount,
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
   public function tranxPayload($reference, CableTopUp $topup, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $reference,
         'transaction_type' => Tranx::CABLE,
         'transaction_id' => $topup->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$topup->product} {$topup->phone_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Notify admin of flagged pending transaction
    */
   protected function notifyAdmins(CableTopUp $topup, string $reference): void
   {
       $this->notifyFlaggedAdmins('cable', $topup->user, $reference, $topup->amount_requested);
   }
}
