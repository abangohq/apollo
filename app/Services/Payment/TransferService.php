<?php

namespace App\Services\Payment;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\Withdrawal;
use App\Services\Payment\MonnifyService;
use App\Services\Payment\RedbillerService;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Conditionable;

class TransferService
{
   use Conditionable, WalletEntity;

   public function __construct(public MonnifyService $monnify, public RedbillerService $redbiller)
   {
      //
   }

   /**
    * Handle the withdrawal funds transfer via specified provider
    */
   public function handle(Withdrawal $withdrawal)
   {
      $platform = $withdrawal->platform;
      $payload = $this->payoutPayload($withdrawal);

      if ($platform == Tranx::WD_MONNIFY->value) {
         $this->viaMonnify($withdrawal, $payload);
      } elseif ($platform == Tranx::WD_REDBILLER->value) {
         $this->viaRedbiller($withdrawal, $payload);
      }
   }

   /**
    * Handle transfer via redbiller provider
    */
   public function viaRedbiller(Withdrawal $withdrawal, array $payload)
   {
      try {
         $response = $this->redbiller->transfer($payload);

         rescue(fn() => logger(json_encode($response)));

         if ($response->response == 400 || $response->response == 429) {
            return $this->redbiller->instantWithdrawalReversal($withdrawal);
         }

         if (in_array($response?->meta?->status, ['Rejected', 'Cancelled', 'Declined'])) {
            return $this->redbiller->reversal($withdrawal->refresh());
         }

         $status = match ($response?->meta?->status) {
            'Approved' => Status::SUCCESSFUL,
            'Pending' => Status::PENDING,
            default => Status::FAILED
         };

         $saveAttributes = [
            'reference' => $response?->details?->reference,
            'pr_status' => $response?->meta?->status,
            'status' => $status
         ];

         $this->updateWithdrawal($saveAttributes, $withdrawal);
      } catch (\Throwable $th) {
         Log::alert($th);
         throw $th;
      }
   }

   /**
    * Handle transfer via monnify provider
    */
   public function viaMonnify(Withdrawal $withdrawal, array $payload)
   {
      try {
         $response = $this->monnify->transfer($payload);

         if (in_array($response?->responseBody?->status, ['SUCCESS', 'COMPLETED'])) {
            $status = Status::SUCCESSFUL;
         } else if (in_array($response?->responseBody?->status, ['PENDING', 'IN_PROGRESS', 'AWAITING_PROCESSING'])) {
            $status = Status::PENDING;
         } else {
            $status = Status::FAILED;
         }

         $saveAttributes = [
            'reference' => $response?->responseBody?->reference,
            'pr_status' => $response?->responseBody?->status,
            'status' => $status
         ];

         $this->updateWithdrawal($saveAttributes, $withdrawal);
      } catch (\Throwable $th) {
         Log::alert($th);
         throw $th;
      }
   }

   /**
    * Update the withdrawal status afte transfer
    */
   public function updateWithdrawal(array $resPayload, Withdrawal $withdrawal)
   {
      $updated = (bool) $withdrawal->update([
         'provider_reference' => $resPayload['reference'],
         'provider_status' => $resPayload['pr_status'],
         'status' => $resPayload['status'],
      ]);

      Utils::LogAlertIf(!$updated, 'Transfer service withdraw error', [$withdrawal, $resPayload]);
   }

   /**
    * Generate payout payload
    */
   public function payoutPayload(Withdrawal $withdrawal)
   {
      return [
         'amount' => $withdrawal->amount,
         'reference' => $withdrawal->reference,
         'account_name' => $withdrawal->account_name,
         'account_number' => $withdrawal->account_number,
         'bank_code' => $withdrawal->bank_code,
         'bank' => $withdrawal->bank
      ];
   }
}
