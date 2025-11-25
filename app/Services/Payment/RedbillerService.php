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

class RedbillerService
{
   use WalletEntity;

   public function __construct(){

   }

   /**
    * Verify the payout transaction
    */
    public function verifyAllTransactions($reference, $type)
    {
      $data = ['reference' => $reference];

       switch ($type) {
         case 'transfer':
             $endpoint = '1.0/payout/bank-transfer/status';
             break;
 
         case 'airtime':
             $endpoint = '1.0/bills/airtime/purchase/status';
             break;

         case 'data':
            $endpoint = '1.0/bills/data/plans/purchase/status';
            break;

         case 'wifi':
            $endpoint = '1.0//bills/internet/plans/purchase/status';
            break;

         case 'cable':
            $endpoint = '1.0/bills/cable/plans/purchase/status';
            break;

         case 'disco':
            $endpoint = '1.0/bills/disco/purchase/status';
            break;

         case 'betting':
            $endpoint = "1.5/bills/betting/account/payment/status";
            break;
 
         default:
             throw new \InvalidArgumentException("Invalid transaction type: {$type}");
     }

       return $this->http()->post($endpoint, $data)->object();
    }

   /**
    * Verify the payout transaction
    */
   public function verifyTransaction($reference)
   {
      $data = ['reference' => $reference];
      return $this->http()->post('1.0/payout/bank-transfer/status', $data)->object();
   }

   /**
    * Initiate airtime purchase
    */
   public function purchaseAirtime($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->post('1.0/bills/airtime/purchase/create', $payload)->throw()->object();
   }

   /**
    * Verify airtime purchase
    */
   public function verifyAirtimePurchase($payload)
   {
      return $this->http()->post('1.0/bills/airtime/purchase/status', $payload)->object();
   }

   /**
    * Purchase a data plan
    */
   public function purchaseData($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->post('1.0/bills/data/plans/purchase/create', $payload)->object();
   }

   /**
    * Get the available data plans for network
    */
   public function dataPlans($payload)
   {
      return $this->http()->post('1.0/bills/data/plans/list', $payload)->throw()->object();
   }

   /**
    * Verify data purchase status
    */
   public function verifyDataPurchase($payload)
   {
      return $this->http()->post('1.0/bills/data/plans/purchase/status', $payload)->throw()->object();
   }

   /**
    * Initiate betting account topup
    */
   public function fundBetAccount($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->post('1.5/bills/betting/account/payment/create', $payload)->object();
   }

   /**
    * Verify betting account payment
    */
   public function verifyBettingAccountCredit($payload)
   {
      return $this->http()->post('1.5/bills/betting/account/payment/status', $payload)->object();
   }

   /**
    * verify the betting account to topup
    */
   public function verifyBettingAccount($payload)
   {
      return $this->http()->post('1.5/bills/betting/account/verify', $payload)->object();
   }

   /**
    * Get the available betting providers
    */
   public function bettingProviders()
   {
      return $this->http()->post('1.4/bills/betting/providers/list', 'GET')->object();
   }

   /**
    * Purchase a Cable service plan
    */
   public function purchaseCablePlan($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->timeout(120)->retry(2)->post('1.0/bills/cable/plans/purchase/create', $payload)->object();
   }

   /**
    * Get the list of cable plans
    */
   public function cablePlans($payload)
   {
      return $this->http()->timeout(90)->retry(2)->post('1.0/bills/cable/plans/list', $payload)->object();
   }

   /**
    * Verify customer cable tv smart card
    */
   public function verifySmartCardNumber($payload)
   {
      return $this->http()->timeout(90)->retry(2)->post('1.0/bills/cable/decoder/verify', $payload)->object();
   }

   /**
    * Verify cable plan purchase is successful
    */
   public function verifyCablePlanPurchase($payload)
   {
      return $this->http()->post('1.0/bills/cable/plans/purchase/status', $payload)->object();
   }

   /**
    * Electricity purchase
    */
   public function purchaseDisco($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->timeout(180)->post('1.0/bills/disco/purchase/create', $payload)->object();
   }

   /**
    * Verify wifi purchase
    */
   public function verifyDiscoPurchase($payload)
   {
      return $this->http()->post('1.0/bills/disco/purchase/status', $payload)->object();
   }

   /**
    * Verify meter number for disco purchase
    */
   public function verifyMeterNumber($payload)
   {
      return $this->http()->post('1.0/bills/disco/meter/verify', $payload)->object();
   }

   /**
    * Purchase wifi plans
    */
   public function purchaseWiFi($payload)
   {
      $this->setup3D($payload['reference']);
      return $this->http()->post('1.0/bills/internet/plans/purchase/create', $payload)->object();
   }

   /**
    * verify device number
    */
   public function verifyDeviceNumber($payload)
   {
      return $this->http()->timeout(90)->retry(2)->post('1.0/bills/internet/device/verify', $payload)->object();
   }

   /**
    * Get the available wifi plans
    */
   public function wifiPlans($payload)
   {
      return $this->http()->post('1.0/bills/internet/plans/list', $payload)->object();
   }

   /**
    * Verify wifi plan purchase status
    */
   public function verifyWifiPurchase($payload)
   {
      return $this->http()->timeout(120)->retry(3)->post('1.0/bills/internet/plans/purchase/status', $payload)->object();
   }

   /**
    * Get balance on redbiller
    */
   public function balance()
   {
      return $this->http()->get('1.0/get/balance')->object();
   }

   /**
    * Initiate bank transfer to redbiller
    */
   public function transfer($payload)
   {
      $this->setup3D($payload['reference']);
      $appName = env('APP_NAME');

      return $this->http()->timeout(120)->post("1.0/payout/bank-transfer/create", [
         'amount' => $payload['amount'],
         'narration' => "{$appName} Payout",
         'reference' => $payload['reference'],
         'account_no' => $payload['account_number'],
         'bank_code' => $payload['bank']['bank_code'],
         'callback_url' => config('services.redbiller.transferCallback')
      ])->object();
   }

   /**
    * Initiate 3D reference to file pointer
    * @todo change 3D auth url
    */
   public function setup3D($reference): void
   {
      Http::post("https://3d-auth.getkoyn.com/api/redbiller/$reference");
   }

   /**
    * The http client with auth credentials
    *
    * @return \Illuminate\Support\Facades\Http
    */
   public function http()
   {
      return Http::redbiller();
   }

   /**
    * Resolve bank transfer event
    */
   public function resolveEvent($eventPayload)
   {
      $reference = $eventPayload['details']['reference'];
      $withdrawal = Withdrawal::findByRef($reference)->first();

      if (is_null($withdrawal)) {
         return;
      }

      if ($eventPayload['meta']['status'] == 'Approved') {
         return $this->success($withdrawal);
      }

      if (in_array($eventPayload['meta']['status'], ['Rejected', 'Cancelled', 'Declined'])) {
         return $this->reversal($withdrawal);
      }
   }

   /**
    * Handle successful disbursement
    */
   public function success(Withdrawal $withdrawal)
   {
      $response = $this->verifyTransaction($withdrawal->reference);

      if ($response?->response == 200 && $response?->meta?->status == 'Approved') {
         $withdrawal->update([
            'status' => Status::SUCCESSFUL,
            'provider_status' => $response->meta->status,
            'provider_reference' => $response->details->reference,
         ]);

         $withdrawal->user->notify(new WithdrawCompletedNotification($withdrawal));
      }
   }

   /**
    * Handle a reversal disbursement
    */
   public function reversal(Withdrawal $withdrawal)
   {
      $response = $this->verifyTransaction($withdrawal->reference);

      logger()->info(json_encode($response));

      if (
         !in_array(@$response?->meta?->status, ['Rejected', 'Cancelled', 'Declined']) ||
         TransactionRepository::hasReversal($withdrawal->id, Tranx::WITHDRAW)
      ) {
         return;
      }

      try {
         DB::beginTransaction();

         $this->credit($withdrawal->user_id, $withdrawal->amount);
         $tranxPayload = $this->reversalTranxPayload($withdrawal);

         WalletTransaction::create($tranxPayload);

         $withdrawal->update([
            'status' => Status::FAILED,
            'provider_status' => $response->meta->status,
            'provider_reference' => $response->details->reference,
         ]);

         $withdrawal->user->notify(new WithdrawReversalNotification($withdrawal));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }

   /**
    * Instant reversal for transfer initiation.
    * also double check if its still 400
    */
   public function instantWithdrawalReversal(Withdrawal $withdrawal)
   {
      $response = $this->verifyTransaction($withdrawal->reference);

      logger(json_encode($response));

      if ($response->response != 400) {
         return;
      }

      try {
         DB::beginTransaction();

         $this->credit($withdrawal->user_id, $withdrawal->amount);
         $tranxPayload = $this->reversalTranxPayload($withdrawal);

         WalletTransaction::create($tranxPayload);

         $withdrawal->update([
            'status' => Status::FAILED,
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
