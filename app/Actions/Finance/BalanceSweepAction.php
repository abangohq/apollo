<?php

namespace App\Actions\Finance;

use App\Enums\Tranx;
use App\Http\Requests\Admin\Finance\CryptoWithdrawalRequest;
use App\Models\CryptoWithdrawal;
use App\Models\SystemSetting;
use App\Services\Crypto\VaultodyService;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;

class BalanceSweepAction
{
   use WalletEntity;

   public $recipientAddress;

   public function __construct(public CryptoWithdrawalRequest $request, public VaultodyService $vaultodyService)
   {
      $this->recipientAddress = SystemSetting::where('key', 'sweep_address')->value('value');
      logger($this->recipientAddress);
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      try {
         DB::beginTransaction();

         $response = $this->vaultodyService->walletWithdrawal(
            collect($this->request->safe())->merge(['address' => $this->recipientAddress])->toArray()
         );

         $withdrawal = CryptoWithdrawal::create($this->withdrawPayload($response));

         DB::commit();

         return $withdrawal;
      } catch (\Throwable $th) {
         DB::rollBack();
         logger()->debug($th->getMessage());
         throw $th; // throw for handler
      }
   }

   /**
    * crpyto waithdrawal payload
    */
   public function withdrawPayload(object $response)
   {
      return [
         'staff_id' => request()->user()->id,
         'platform' => Tranx::VAULTODY,
         'recipient_address' => $this->recipientAddress,
         'amount' => $this->request->amount,
         'request_status' => $response->data->item->transactionRequestStatus,
         'request_id' => $response->data->item->transactionRequestId,
      ];
   }
}
