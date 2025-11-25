<?php

namespace App\Services\Bills;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Jobs\Bills\ResolveDataPurchase;
use App\Models\DataTopUp;
use App\Models\WalletTransaction;
use App\Notifications\User\DataCompleteNotification;
use App\Notifications\User\DataReversalNotification;
use App\Repositories\TransactionRepository;
use App\Services\Payment\RedbillerService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataBillService
{
   use WalletEntity;

   public function __construct(public RedbillerService $redbillerService)
   {
   }

   /**
    * Dispatch data purchase to redbiller
    */
   public function handle(DataTopUp $topup)
   {
      try {
         $response = $this->redbillerService->purchaseData($this->purchasePayload($topup));

         if ($response->response != 200) {
            return ResolveDataPurchase::dispatch($topup);
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
            "amount_paid" => $response->details->amount_paid,
            "discount_percentage" => $response->details->discount_percentage,
            "discount_value" => $response->details->discount_value,
            "provider_status" => $response->meta->status
         ]);

         if ($status->value == 'successful') {
            $topup->user->notify(new DataCompleteNotification($topup));
         }
      } catch (\Throwable $th) {
         ResolveDataPurchase::dispatch($topup);
      }
   }

   /**
    * Data purchase payload
    */
   public function purchasePayload($topup)
   {
      return [
         "product" =>  $topup->product,
         "code" =>  $topup->code,
         "phone_no" =>  $topup->phone_no,
         "ported" =>  "false",
         "callback_url" => config('services.redbiller.data_callback'),
         "reference" =>  $topup->reference
      ];
   }

   /**
    * Resolve purchase trans. relation to same topup
    */
   public function resolve(DataTopUp $topup)
   {
      $response = $this->redbillerService->verifyDataPurchase([
         'reference' => $topup->reference
      ]);

      if (
         $response->response == 200 && $response?->meta?->status !== 'Approved' && $response?->meta?->status !== 'Pending' &&
         !TransactionRepository::hasReversal($topup->id, Tranx::DATA)
      ) {
         return $this->reversal($topup);
      }

      if ($response->response == 200 && @$response?->meta?->status == 'Approved') {
         $topup->update(['status' => Status::SUCCESSFUL]);
         $topup->user->notify(new DataCompleteNotification($topup));
      }
   }

   /**
    * Initiate a reversal transaction
    */
   public function reversal(DataTopUp $topup)
   {
      DB::beginTransaction();
      try {
         $this->credit($topup->user_id, $topup->amount_requested);
         $tranxPayload = $this->reversalTranxPayload($topup);

         WalletTransaction::create($tranxPayload);
         $topup->update(['status' => Status::FAILED]);
         $topup->user->notify(new DataReversalNotification($topup));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(DataTopUp $topup, $charge = 0)
   {
      return [
         'user_id' => $topup->user_id,
         'reference' => "RVSL-{$topup->reference}",
         'transaction_type' => Tranx::DATA,
         'transaction_id' => $topup->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl - {$topup->product} {$topup->phone_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $topup->user->wallet->balance,
         'is_reversal' => true
      ];
   }
}
