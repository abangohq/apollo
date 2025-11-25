<?php

namespace App\Actions\Payment;

use App\Enums\Prefix;
use App\Enums\Tranx;
use App\Enums\Status;
use App\Http\Requests\Crypto\CreateSwapRequest;
use App\Jobs\Crypto\ResolveSwapTransaction;
use App\Models\CryptoAsset;
use App\Models\SwapTransaction;
use App\Models\WalletTransaction;
use App\Repositories\CryptoRepository;
use App\Services\Crypto\ChangellyService;
use App\Services\Crypto\VaultodyService;
use App\Services\Crypto\XprocessingService;
use App\Support\Utils;
use App\Traits\WalletEntity;
use App\Notifications\User\SwapPayinAddressNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CryptoSwapAction
{
   use WalletEntity;

   public function __construct(
      public CreateSwapRequest $request,
      public XprocessingService $xprocess,
      public ChangellyService $changelly,
      public CryptoRepository $cryptoRepo,
      public VaultodyService $vaultody
   ) {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      DB::beginTransaction();
      try {
         $swap = $this->createSwap();
         $transaction = $this->swapTransaction($swap);
        // Send pay-in address to user's email
        rescue(fn() => $transaction->user->notify(new SwapPayinAddressNotification($transaction)));
         ResolveSwapTransaction::dispatch($transaction)->afterCommit();
         DB::commit();

         return $transaction;
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // throw for global catch
      }
   }

   /**
    * first or create address to use if app address for swap
    */
   public function appAddress(string $symbol)
   {
      $chainWallet = $this->cryptoRepo->chainWallet(request()->user()->id, $symbol);

      if ($chainWallet) {
         return $chainWallet->only(['address', 'chain']);
      }

      $asset = CryptoAsset::whereSymbol($symbol)->firstOrFail();

      if (strtoupper($symbol) == 'BTC') {
         $wallet = $this->vaultody->createWallet($asset, request()->user());
         return $wallet->only(['address', 'chain']);
      }

      $wallet = $this->xprocess->createWallet($asset, request()->user());
      return $wallet->only(['address', 'chain']);
   }

   /**
    * Get the swap payload to send
    */
   public function swapPayload()
   {
      if (empty($this->request->address) && $this->request->app_address) {
         $address = $this->appAddress($this->request->to)['address'];
      } else {
         $address = $this->request->address;
      }

      return collect([
         'from' => $this->request->from,
         "to" => $this->request->to,
         "address" => $address,
         "amountFrom" => $this->request->amountFrom,
         "refundAddress" => $this->request->refundAddress
      ])
         ->when($this->request->swap_type == 'fixed')->put('rateId', $this->request->rateId)
         ->toArray();
   }

   /**
    * Iniatiate the swap based on swap type
    */
   public function createSwap()
   {
      $payload = $this->swapPayload();

      if ($this->request->swap_type == 'fixed') {
         $swap = $this->changelly->createFixedTransaction($payload);
      } else {
         $swap = $this->changelly->createTransaction($payload);
      }

      if (array_key_exists('error', $swap)) {
         throw ValidationException::withMessages(['from' => [$swap['error']['message']]]);
      }

      return $swap['result'];
   }

   /**
    * Save the swap transaction record in the DB
    */
   public function swapTransaction(array $swap)
   {
      $transaction = SwapTransaction::create([
         'reference' => Utils::generateReference(Prefix::SWAP),
         'user_id' => request()->user()->id,
         'swap_tranx_id' => $swap['id'],
         'swap_type' => $swap['type'],
         // Normalize initial status: provider returns 'new' on creation,
         // treat it as 'waiting' to align with subsequent getStatus semantics.
         'status' => ($swap['status'] === 'new' ? 'waiting' : $swap['status']),
         'currency_from' => $swap['currencyFrom'],
         'currency_to' => $swap['currencyTo'],
         'payin_address' => $swap['payinAddress'],
         'payout_address' => $swap['payoutAddress'],
         'refund_address' => @$swap['refundAddress'],
         'is_app_address' => empty($this->request->address) && $this->request->app_address,
         'amount_expected_from' => $swap['amountExpectedFrom'],
         'amount_expected_to' => $swap['amountExpectedTo'],
         'pay_till' => @$swap['payTill'],
         'network_fee' => $swap['networkFee'],
         'track_url' => @$swap['trackUrl'],
      ]);

      WalletTransaction::create([
         'user_id' => request()->user()->id,
         'reference' => $transaction->reference,
         'transaction_type' => Tranx::SWAP,
         'transaction_id' => $transaction->id,
         'entry' => Tranx::DEBIT,
         'status' => Status::PENDING,
         'narration' => "Swap {$transaction->currency_from} to {$transaction->currency_to}",
         'currency' => $transaction->currency_to,
         'amount' => $transaction->amount_expected_to,
         'charge' => 0,
         'total_amount' => $transaction->amount_expected_to,
         'wallet_balance' => request()->user()->wallet()->value('balance') ?? 0.0
      ]);

      return $transaction;
   }

   /**
    * Get the transaction payload
    */

}
