<?php

namespace App\Actions\Payment;

use App\Enums\Prefix;
use App\Enums\Tranx;
use App\Http\Requests\Wallet\WithdrawalRequest;
use App\Jobs\User\ProcessWithdraw;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Notifications\Admin\WithdrawalNotice;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Facades\Notification;

class WithdrawAction
{
   use Conditionable, WalletEntity;

   /**
    * The Payment provider to fullfill payment
    */
   public string $gateway;

   /**
    * The system settings for single withdraw threshold
    */
   public mixed $autoWithdrawMax;

   /**
    * The user bank account to process payment
    *
    * @var \App\Models\BankAccount
    */
   public $bank;

   /**
    * The channel to send the withdraw action
    * @var string    auto|manual
    */
   public string $withdrawalMode;

   public function __construct(public WithdrawalRequest $request)
   {
      $this->setSettings()->setBank();
   }

   /**
    * Handle the action, and parallel lock on the same
    * request object for concurrency
    */
   public function handle()
   {
      DB::beginTransaction();
      try {
         $reference = Utils::generateReference(Prefix::WALLET_WITHDRAWAL);
         $this->debit($this->request->user()->id, $this->request->amount);

         $withdrawal = Withdrawal::create($this->withdrawPayload($reference));
         $tranx = WalletTransaction::create($this->tranxPayload($withdrawal));

         // If flagged, force manual and skip auto processing
-        $flagged = ($this->request->user()->is_flagged ?? false) || ($this->request->user()->wallet?->is_flagged ?? false);
+        $flagged = $this->request->user()->requiresManualReview();

         $this->when(!$flagged && $this->channel() == Tranx::AUTO, fn () => ProcessWithdraw::dispatch($withdrawal));
         $this->notifyAdmin($withdrawal);

         DB::commit();

         return $tranx;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for global handle
      }
   }

   /**
    * Set the system settings for actions workflow
    */
   public function setSettings()
   {
      $this->autoWithdrawMax = SystemSetting::where('key', 'max_automatic_withdrawal_amount')->value('value');
      $this->withdrawalMode = SystemSetting::where('key', 'withdrawal_mode')->value('value');
      $this->gateway = strtoupper(SystemSetting::where('key', 'payment_gateway')->value('value'));

      return $this;
   }

   /**
    * Set the bank account to handle the payment
    */
   public function setBank()
   {
      $bank = $this->request->user()->banks()
         ->where('id', $this->request->bank_id)->first();

      abort_if(is_null($bank), 409, 'We are unable to retrieve the selected bank account.');

      $this->bank = $bank;
   }

   /**
    * Get the channel in which to send the transaction to
    */
   public function channel()
   {
      $user = $this->request->user();
-     $flagged = ($user->is_flagged ?? false) || ($user->wallet?->is_flagged ?? false);
+     $flagged = $user->requiresManualReview();
      if ($flagged) {
         return Tranx::MANUAL;
      }

      return ($this->request->amount <= $this->autoWithdrawMax &&
         $this->withdrawalMode == 'automatic') ? Tranx::AUTO : Tranx::MANUAL;
   }

   /**
    * Get the withdrawal payload
    */
   public function withdrawPayload(string $reference)
   {
      return [
         'user_id' => $this->request->user()->id,
         'amount' => $this->request->amount,
         'status' => Tranx::TRANX_PENDING,
         'reference' => $reference,
         'bank_code' => $this->bank->bank_code,
         'bank_id' => $this->bank->bank_id,
         'bank_name' => $this->bank->bank_name,
         'account_name' => $this->bank->account_name,
         'account_number' => $this->bank->account_number,
         'bank_logo' => $this->bank->image,
         'provider_reference' => null,
         'provider_status' => null,
         'settled_by' => null,
         'rejection_id' => null,
         'platform' => $this->gateway,
         'channel' => $this->channel()
      ];
   }

   /**
    * Get the transaction payload
    */
   public function tranxPayload(Withdrawal $withdrawal, int $charge = 0)
   {
      return [
         'user_id' => $this->request->user()->id,
         'reference' => $withdrawal->reference,
         'transaction_type' => Tranx::WITHDRAW,
         'transaction_id' => $withdrawal->id,
         'entry' => Tranx::DEBIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "N{$withdrawal->amount} Withdrawal request",
         'amount' => $withdrawal->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $withdrawal->amount,
         'wallet_balance' => $this->request->user()->wallet()->value('balance')
      ];
   }

   /**
    * Dispatch notification for manual transaction
    */
   public function notifyAdmin(Withdrawal $withdrawal)
   {
      $staffs = User::staff()->active()->take(3)->get();

      if ($staffs->count() > 0 && $withdrawal->channel == Tranx::MANUAL->value) {
         rescue(fn () => Notification::send($staffs, new WithdrawalNotice($withdrawal)));
      }
   }
}
