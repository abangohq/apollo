<?php

namespace App\Services\Payment;

use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\User\WithdrawCompletedNotification;
use App\Notifications\User\WithdrawReversalNotification;
use App\Repositories\TransactionRepository;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonnifyService
{
   use WalletEntity;

   /**
    * The http client with auth credentials
    *
    * @return \Illuminate\Support\Facades\Http
    */
   public function http()
   {
      return Http::monnify();
   }

   /**
    * Re-verify transfer status for event
    */
   public function transferStatus($reference)
   {
      return $this->http()->timeout(240)->retry(2, 100)
         ->get('v2/disbursements/single/summary', ['reference' => $reference])->throw()->object();
   }

   /**
    * Initiate a transfer to monnify
    */
   public function transfer(array $payload, $description = 'Funds Transfer')
   {
      $bankCode = is_null($payload['bank']['monnify_bank_code']) ?
         $payload['bank']['bank_code'] :
         $payload['bank']['monnify_bank_code'];

         $response = $this->http()->timeout(240)->retry(2, 100)->post('/v2/disbursements/single', [
            'currency' => 'NGN',
            'sourceAccountNumber' => config('services.monnify.account_number'),
            'amount' => $payload['amount'],
            'narration' => $description,
            'reference' => $payload['reference'],
            'destinationAccountNumber' => $payload['account_number'],
            'destinationBankCode' => $bankCode,
         ]);

      return $response->object();
   }

   /**
    * Resolve bank transfer event
    */
   public function resolveEvent($eventPayload)
   {
      $reference = $eventPayload['eventData']['reference'];
      $withdrawal = Withdrawal::findByRef($reference)->first();

      if (is_null($withdrawal)) {
         return;
      }

      if ($eventPayload['eventType'] == 'SUCCESSFUL_DISBURSEMENT') {
         return $this->success($withdrawal);
      }

      if (in_array($eventPayload['eventType'], ['REVERSED_DISBURSEMENT', 'FAILED_DISBURSEMENT'])) {
         return $this->reversal($withdrawal);
      }
   }

   /**
    * Handle successful disbursement
    */
   public function success(Withdrawal $withdrawal)
   {
      $response = $this->transferStatus($withdrawal->reference);

      if ($response?->responseBody?->status == 'SUCCESS') {
         $withdrawal->update([
            'status' => Status::SUCCESSFUL,
            'provider_status' => $response->responseBody->status,
            'provider_reference' => $response->responseBody->transactionReference,
         ]);

         $withdrawal->user->notify(new WithdrawCompletedNotification($withdrawal));
      }
   }

   /**
    * Handle a reversal disbursement
    */
   public function reversal(Withdrawal $withdrawal)
   {
      $response = $this->transferStatus($withdrawal->reference);

      if (
         !in_array($response?->responseBody?->status, ['FAILED', 'REVERSED']) ||
         TransactionRepository::hasReversal($withdrawal->id, Tranx::WITHDRAW)
      ) {
         return;
      }

      DB::beginTransaction();
      try {
         $this->credit($withdrawal->user_id, $withdrawal->amount);
         $tranxPayload = $this->reversalTranxPayload($withdrawal);

         WalletTransaction::create($tranxPayload);

         $withdrawal->update([
            'status' => Status::FAILED,
            'provider_status' => $response->responseBody->status,
            'provider_reference' => $response->responseBody->transactionReference,
         ]);

         $withdrawal->user->notify(new WithdrawReversalNotification($withdrawal));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Get the reversal transaction payload
    */
   public function reversalTranxPayload(Withdrawal $withdrawal, $charge = 0)
   {
      return [
         'user_id' => $withdrawal->user_id,
         'reference' => "RVSL-{$withdrawal->reference}",
         'transaction_type' => Tranx::WITHDRAW,
         'transaction_id' => $withdrawal->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "Rvsl of N{$withdrawal->amount} withdrawal request",
         'amount' => $withdrawal->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $withdrawal->amount,
         'wallet_balance' => $withdrawal->user->wallet()->value('balance'),
         'is_reversal' => true,
      ];
   }
}
