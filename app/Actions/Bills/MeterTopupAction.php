<?php

namespace App\Actions\Bills;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Bills\PurchaseMeterRequest;
use App\Models\MeterTopUp;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Notifications\Admin\FlaggedTransactionNotice;
use App\Notifications\User\MeterCompleteNotification;
use App\Services\Bills\MeterBillService;
use App\Support\Utils;
use App\Traits\WalletEntity;
use App\Traits\AdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MeterTopupAction
{
   use WalletEntity, AdminNotifier;

   public function __construct(public PurchaseMeterRequest $request, public MeterBillService $billService)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      $reference = Utils::generateReference(Prefix::METER_TOPUP);

      DB::beginTransaction();
      try {
         $this->debit($this->request->user()->id, $this->request->amount);

         $flagged = $this->request->user()->requiresManualReview();

         if ($flagged) {
            // Do not call provider; create a pending topup with minimal fields
            $topup = MeterTopUp::create($this->flaggedTopupPayload($reference));
            $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));
            $this->notifyAdmins($topup, $reference);
         } else {
            $response = $this->billService->handle($this->billerPayload($reference));
            $topup = MeterTopUp::create($this->topupPayload($response, $reference));
            $tranx = WalletTransaction::create($this->tranxPayload($reference, $topup));
            rescue(fn () => $topup->user->notify(new MeterCompleteNotification($topup, $response?->details?->token)));
         }

         DB::commit();

         return $tranx;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Meter Purchase payload
    */
   public function billerPayload($reference)
   {
      return [
         'product' => $this->request->product,
         'meter_no' => $this->request->meter_no,
         'customer_name' => $this->request->customer_name,
         'amount' => $this->request->amount,
         'meter_type' => $this->request->meter_type,
         'phone_no' => $this->request->user()->phone,
         "callback_url" => config('services.redbiller.meter_callback'),
         'reference' => $reference
      ];
   }

   /**
    * Create the datatopup payload
    */
   public function topupPayload(object $response, string $reference)
   {
      if ($response->meta->status == 'Approved') {
         $status = Status::SUCCESSFUL;
      } elseif ($response->meta->status == 'Pending') {
         $status = Status::PENDING;
      }

      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->product,
         'meter_no' => $this->request->meter_no,
         'meter_type' => $this->request->meter_type,
         "customer_name" => $this->request->customer_name,
         "phone_no" => $this->request->user()->phone,
         'amount_requested' => $this->request->amount,
         'amount_paid' => $response->details->amount_paid,
         'discount_percentage' =>  $response->details->discount_percentage,
         'discount_value' =>  $response->details->discount_value,
         'token' =>  $response->details->token,
         'reference' => $reference,
         'status' => $status ?? Status::FAILED,
         'provider_status' => $response->meta->status,
      ];
   }

   /**
    * Create flagged payload without provider response
    */
   protected function flaggedTopupPayload(string $reference): array
   {
      return [
         'user_id' => $this->request->user()->id,
         'product' => $this->request->product,
         'meter_no' => $this->request->meter_no,
         'meter_type' => $this->request->meter_type,
         "customer_name" => $this->request->customer_name,
         "phone_no" => $this->request->user()->phone,
         'amount_requested' => $this->request->amount,
         'amount_paid' => null,
         'discount_percentage' =>  null,
         'discount_value' =>  null,
         'token' =>  null,
         'reference' => $reference,
         'status' => Status::PENDING,
         'provider_status' => null,
      ];
   }

   /**
    * Get the transaction payload
    */
   public function tranxPayload($reference, MeterTopUp $topup, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $reference,
         'transaction_type' => Tranx::METER,
         'transaction_id' => $topup->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$topup->product} {$topup->meter_type} {$topup->meter_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Notify admin of flagged pending transaction
    */
   protected function notifyAdmins(MeterTopUp $topup, string $reference): void
   {
       $this->notifyFlaggedAdmins('meter', $topup->user, $reference, $topup->amount_requested);
   }
}
