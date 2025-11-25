<?php

namespace App\Services\Bills;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\MeterTopUp;
use App\Models\WalletTransaction;
use App\Notifications\User\MeterCompleteNotification;
use App\Notifications\User\MeterReversalNotification;
use App\Repositories\TransactionRepository;
use App\Services\Payment\RedbillerService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;

class MeterBillService
{
   use WalletEntity;

   public function __construct(public RedbillerService $redbillerService)
   {
      //
   }

   /**
    * Dispatch bill purchase to redbiller in sync 
    */
   public function handle(array $payload)
   {
      try {
         $response = $this->redbillerService->purchaseDisco($payload);

         if ($response->response != 200) {
            abort(409, 'Error occured while trying to process your purchase.');
         }

         if ($response->response == 200 && in_array($response->meta->status, ['Cancelled', 'Declined'])) {
            abort(409, 'Error occured while trying to process your purchase.');
         }

         return $response;
      } catch (\Throwable $th) {
         throw $th;
      }
   }

   /**
    * Resolve purchase trans. relation to same topup
    */
   public function resolve(MeterTopUp $topup)
   {
      $response = $this->redbillerService->verifyDiscoPurchase([
         'reference' => $topup->reference
      ]);

      if (
         $response->response == 200 && $response?->meta?->status !== 'Approved' && $response?->meta?->status !== 'Pending' &&
         !TransactionRepository::hasReversal($topup->id, Tranx::METER)
      ) {
         return $this->reversal($topup);
      }

      if ($response->response == 200 && @$response?->meta?->status == 'Approved') {
         $topup->update(['status' => Status::SUCCESSFUL]);
         rescue(fn() => $topup->user->notify(new MeterCompleteNotification($topup, $response?->details?->token)));
      }
   }

   /**
    * Initiate a reversal transaction
    */
   public function reversal(MeterTopUp $topup)
   {
      DB::beginTransaction();
      try {
         $this->credit($topup->user_id, $topup->amount_requested);
         $tranxPayload = $this->reversalTranxPayload($topup);

         WalletTransaction::create($tranxPayload);
         $topup->update(['status' => Status::FAILED]);
         $topup->user->notify(new MeterReversalNotification($topup));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(MeterTopUp $topup, $charge = 0)
   {
      return [
         'user_id' => $topup->user_id,
         'reference' => "RVSL-{$topup->reference}",
         'transaction_type' => Tranx::DATA,
         'transaction_id' => $topup->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl - {$topup->product} {$topup->meter_no}",
         'amount' => $topup->amount_requested,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount_requested,
         'wallet_balance' => $topup->user->wallet->balance,
         'is_reversal' => true
      ];
   }
}
