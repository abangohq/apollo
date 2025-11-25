<?php

namespace App\Services\Bills;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Jobs\Bills\ResolveWifiPurchase;
use App\Models\WalletTransaction;
use App\Models\WifiTopUp;
use App\Notifications\User\WifiCompleteNotification;
use App\Notifications\User\WifiReversalNotification;
use App\Repositories\TransactionRepository;
use App\Services\Payment\RedbillerService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WifiBillService
{
   use WalletEntity;

   public function __construct(public RedbillerService $redbillerService)
   {
      //
   }

   /**
    * Dispatch wifi plan purchase to redbiller
    */
   public function handle(WifiTopUp $topup)
   {
      try {
         $response = $this->redbillerService->purchaseWiFi($this->purchasePayload($topup));

         if ($response->response != 200) {
            return ResolveWifiPurchase::dispatch($topup);
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
            "provider_status" => @$response->meta->status
         ]);

         if ($status->value == 'successful') {
            $topup->user->notify(new WifiCompleteNotification($topup));
         }
      } catch (\Throwable $th) {
         ResolveWifiPurchase::dispatch($topup);
      }
   }

   /**
    * Wifi plan purchase payload
    */
   public function purchasePayload($topup)
   {
      return [
         "product" =>  $topup->product,
         "code" =>  $topup->code,
         "device_no" =>  $topup->device_number,
         'customer_name' => $topup->customer_name,
         "callback_url" => config('services.redbiller.wifi_callback'),
         "reference" =>  $topup->reference,
      ];
   }

   /**
    * Resolve DT purchase trans. relation to same topup
    */
   public function resolve(WifiTopUp $topup)
   {
      $response = $this->redbillerService->verifyWifiPurchase([
         'reference' => $topup->reference
      ]);

      if (
         $response->response == 200 && $response?->meta?->status !== 'Approved' && $response?->meta?->status !== 'Pending' &&
         !TransactionRepository::hasReversal($topup->id, Tranx::WIFI)
      ) {
         return $this->reversal($topup);
      }

      if ($response->response == 200 && @$response?->meta?->status == 'Approved') {
         $topup->update(['status' => Status::SUCCESSFUL]);
         $topup->user->notify(new WifiCompleteNotification($topup));
      }
   }

   /**
    * Initiate a reversal transaction
    */
   public function reversal(WifiTopUp $topup)
   {
      DB::beginTransaction();
      try {
         $tranxPayload = $this->reversalTranxPayload($topup);

         $this->credit($topup->user_id, $topup->amount_requested);
         WalletTransaction::create($tranxPayload);
         $topup->update(['status' => Status::FAILED]);
         $topup->user->notify(new WifiReversalNotification($topup));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(WifiTopUp $topup, $charge = 0)
   {
      return [
         'user_id' => $topup->user_id,
         'reference' => "RVSL-{$topup->reference}",
         'transaction_type' => Tranx::WIFI,
         'transaction_id' => $topup->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl - {$topup->product} {$topup->device_number}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $topup->user->wallet()->value('balance'),
         'is_reversal' => true
      ];
   }
}
