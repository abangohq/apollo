<?php

namespace App\Services\Bills;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Jobs\Bills\ResolveBettingPurchase;
use App\Models\BettingTopUp;
use App\Models\WalletTransaction;
use App\Notifications\User\BettingCompleteNotification;
use App\Notifications\User\BettingReversalNotification;
use App\Notifications\User\CableReversalNotification;
use App\Repositories\TransactionRepository;
use App\Services\Payment\RedbillerService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BettingBillService
{
   use WalletEntity;

   public function __construct(public RedbillerService $redbillerService)
   {
      //
   }

   /**
    * Dispatch data purchase to redbiller
    */
   public function handle(BettingTopUp $topup)
   {
      try {
         $response = $this->redbillerService->fundBetAccount($this->purchasePayload($topup));
         \Log::info($response);

         if ($response->response != 200) {
            return ResolveBettingPurchase::dispatch($topup);
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
            "charge" => $response->details->charge,
            "status" => $status,
            "profile" => $response->details->profile,
            "provider_status" => $response->meta->status
         ]);

         if ($status->value == 'successful') {
            $topup->user->notify(new BettingCompleteNotification($topup));
         }
      } catch (\Throwable $th) {
         ResolveBettingPurchase::dispatch($topup);
      }
   }

   /**
    * Betting purchase payload
    */
   public function purchasePayload(BettingTopUp $topup)
   {
      return [
         'product' => $topup->product,
         'customer_id' => $topup->customer_id,
         'amount' => $topup->amount,
         'phone_no' => $topup->phone_no,
         'callback_url' => config('services.redbiller.betting_callback'),
         "reference" =>  $topup->reference
      ];
   }

   /**
    * Resolve purchase trans. relation to same topup
    */
   public function resolve(BettingTopUp $topup)
   {
      $response = $this->redbillerService->verifyBettingAccountCredit([
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
         $topup->user->notify(new BettingCompleteNotification($topup));
      }
   }

   /**
    * Initiate a reversal transaction
    */
   public function reversal(BettingTopUp $topup)
   {
      DB::beginTransaction();
      try {
         $this->credit($topup->user_id, $topup->amount);
         $tranxPayload = $this->reversalTranxPayload($topup);

         WalletTransaction::create($tranxPayload);
         $topup->update(['status' => Status::FAILED]);
         $topup->user->notify(new BettingReversalNotification($topup));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(BettingTopUp $topup, $charge = 0)
   {
      return [
         'user_id' => $topup->user_id,
         'reference' => "RVSL-{$topup->reference}",
         'transaction_type' => Tranx::BETTING,
         'transaction_id' => $topup->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl - {$topup->product} N{$topup->amount} bet fund",
         'amount' => $topup->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $topup->amount,
         'wallet_balance' => $topup->user->wallet()->value('balance'),
         'is_reversal' => true
      ];
   }
}
