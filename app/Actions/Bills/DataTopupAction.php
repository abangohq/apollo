<?php

namespace App\Actions\Bills;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Bills\PurchaseDataRequest;
use App\Jobs\Bills\PurchaseDataPlan;
use App\Models\DataTopUp;
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

class DataTopupAction
{
   use WalletEntity, AdminNotifier;

   public function __construct(public PurchaseDataRequest $request)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $plan = $this->retrievePlan();
      $reference = Utils::generateReference(Prefix::DATA_TOPUP);

      DB::beginTransaction();
      try {
         $this->debit($this->request->user()->id, $plan->amount);
         $topup = DataTopUp::create($this->topupPayload($plan, $reference));
         $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));

         $flagged = $this->request->user()->requiresManualReview();
         if ($flagged) {
            $this->notifyAdmins($topup, $reference);
         } else {
            PurchaseDataPlan::dispatch($topup);
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
      $plans = app(BillRepository::class)->dataPlans($this->request->network);

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
    * Create the datatopup payload
    */
   public function topupPayload($plan, $reference)
   {
      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->network,
         'name' => $plan->name,
         'code' => $this->request->code,
         'phone_no' => $this->request->phone_no,
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
   public function tranxPayload($reference, DataTopUp $topup, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $reference,
         'transaction_type' => Tranx::DATA,
         'transaction_id' => $topup->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$topup->product} data {$topup->phone_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Notify admin of flagged pending transaction
    */
   protected function notifyAdmins(DataTopUp $topup, string $reference): void
   {
       $this->notifyFlaggedAdmins('data', $topup->user, $reference, $topup->amount_requested);
   }
}
