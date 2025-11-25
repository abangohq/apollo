<?php

namespace App\Services\Bills;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Jobs\Bills\ResolveCablePurchase;
use App\Models\CableTopUp;
use App\Models\WalletTransaction;
use App\Notifications\User\CableCompleteNotification;
use App\Notifications\User\CableReversalNotification;
use App\Repositories\TransactionRepository;
use App\Services\Payment\RedbillerService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CableBillService
{
   use WalletEntity;

   public function __construct(public RedbillerService $redbillerService)
   {
   }

   /**
    * Dispatch cable plan purchase to redbiller
    */
   public function handle(CableTopUp $topup)
   {
      try {
         $response = $this->redbillerService->purchaseCablePlan($this->purchasePayload($topup));

         if ($response->response != 200) {
            return ResolveCablePurchase::dispatch($topup);
         }

         if ($response->response == 200) {
            if ($response->meta->status == 'Approved') {
               $status = Status::SUCCESSFUL;
            } elseif ($response->meta->status == 'Pending') {
               $status = Status::PENDING;
            } else {
               $status = Status::FAILED;
            }
         }

         $topup->update([
            'status' => $status,
            "phone_no" =>  $response->details->phone_no,
            "amount_paid" => $response->details->amount_paid,
            "discount_percentage" => $response->details->discount_percentage,
            "discount_value" => $response->details->discount_value,
            "status" => $status,
            "provider_status" => $response->meta->status
         ]);

         if ($status->value == 'successful') {
            $topup->user->notify(new CableCompleteNotification($topup));
         }
      } catch (\Throwable $th) {
         ResolveCablePurchase::dispatch($topup);
      }
   }

   /**
    * Cable plan purchase payload
    */
   public function purchasePayload(CableTopUp $topup)
   {
      return [
         "product" => strtoupper($topup->product),
         "code" =>  $topup->code,
         "phone_no" =>  $topup->user->phone,
         'smart_card_no' => $topup->smart_card_no,
         'customer_name' => $topup->customer_name,
         "callback_url" => config('services.redbiller.cable_callback'),
         "reference" =>  $topup->reference
      ];
   }

   /**
    * Resolve purchase trans. relation to same topup
    */
   public function resolve(CableTopUp $topup)
   {
      $response = $this->redbillerService->verifyCablePlanPurchase([
         'reference' => $topup->reference
      ]);

      if (
         $response->response == 200 && $response?->meta?->status !== 'Approved' && $response?->meta?->status !== 'Pending' &&
         !TransactionRepository::hasReversal($topup->id, Tranx::BETTING)
      ) {
         return $this->reversal($topup);
      }

      if ($response->response == 200 && @$response?->meta?->status == 'Approved') {
         $topup->update(['status' => Status::SUCCESSFUL]);
         $topup->user->notify(new CableCompleteNotification($topup));
      }
   }

   /**
    * Initiate a reversal transaction
    */
   public function reversal(CableTopUp $topup)
   {
      DB::beginTransaction();
      try {
         $this->credit($topup->user_id, $topup->amount_requested);
         $tranxPayload = $this->reversalTranxPayload($topup);

         WalletTransaction::create($tranxPayload);
         $topup->update(['status' => Status::FAILED]);
         $topup->user->notify(new CableReversalNotification($topup));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(CableTopUp $topup, $charge = 0)
   {
      return [
         'user_id' => $topup->user_id,
         'reference' => "RVSL-{$topup->reference}",
         'transaction_type' => Tranx::CABLE,
         'transaction_id' => $topup->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl - {$topup->product} {$topup->smart_card_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $topup->user->wallet->balance,
         'is_reversal' => true
      ];
   }
}
