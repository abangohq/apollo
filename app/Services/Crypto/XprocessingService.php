<?php

namespace App\Services\Crypto;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\CryptoAsset;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\User\CryptoCompleteNotification;
use App\Repositories\CryptoRepository;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RangeException;

class XprocessingService
{
   use WalletEntity;

   public function __construct(public CryptoRepository $cryptoRepo, public CoinGeckoService $coinGeckoService)
   {
      //
   }

   /**
    * Generate crypto assets address for user
    */
   public function generateAddress($data)
   {
      return $this->http()->post('CreateClientWallet', $data)->object();
   }

   /**
    * Fetch client wallet infomation address
    */
   public function fetchWallets($id)
   {
      return $this->http()->get("GetClientWallets/{$id}")->object();
   }

   /**
    * Create local wallet for user after getting the address
    *
    * @return \Illuminate\Database\Eloquent\Model|null;
    */
   public function createWallet(CryptoAsset $asset, User $user)
   {
      // Enforce wallet creation limit: non–Tier 2 users max 2 wallets
      if (($user->tier_id ?? null) !== 2) {
         $walletCount = CryptoWallet::where('user_id', $user->id)->count();
         if ($walletCount >= 2) {
            abort(403, 'You can only create up to 2 crypto wallets unless you are Tier 2.');
         }
      }

      $data = ['Currency' => $asset->symbol, 'ClientId' => $user->id];
      $response = $this->generateAddress($data);

      logger(json_encode($response));

      if (@$response?->type == 'WalletIsAlreadyExist') {
         $response = $this->fetchWallets($user->id);
         logger()->info(json_encode($response));
         $response = collect($response)->first(fn($item) =>  $item->currency === $asset->symbol);
      }

      return CryptoWallet::create([
         'chain' => strtoupper($response->currency),
         'address' => $response->address,
         'crypto_asset_id' => $asset->id,
         'user_id' => $user->id
      ]);
   }

   /**
    * withdraw from client wallet
    */
   public function withdraw($data)
   {
      return $this->http()->post('Withdraw', $data)->object();
   }

   /**
    * Get the reversal transaction payload
    */
   public function tranxPayload(CryptoTransaction $transaction)
   {
      return [
         'user_id' => $transaction->user_id,
         'reference' => $transaction->reference,
         'transaction_type' => Tranx::CRYPTO,
         'transaction_id' => $transaction->id,
         'entry' => Tranx::CREDIT,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$transaction->payout_currency} {$transaction->payout_amount} {$transaction->crypto} payout",
         'amount' => $transaction->payout_amount,
         'charge' => 0,
         'total_amount' => $transaction->payout_amount,
         'wallet_balance' => $transaction->user->wallet->balance,
      ];
   }

   /**
    * Get the crypto transaction Payload
    */
   public function cryptoTranxPayload(CryptoWallet $wallet, array $payload)
   {
      $range = $this->cryptoRepo->rateRange($payload["TotalAmountUSD"]);
      $confirmations = 3;

      if ($payload['Currency'] == 'ETH') {
         $crypto_id = 'ethereum';
     } elseif (stripos($payload['Currency'], 'USDT') !== false) {
         $crypto_id = 'usdt';
     } else {
         $crypto_id = 'usdc';
     }  

      $dollarPrice = $this->coinGeckoService->cryptoPrice($crypto_id);

      if (is_null($range)) {
         throw new RangeException('dollar price not in range.');
      }

      $feePercent = (float) ($range->fee ?? 0);
      $isStable = $this->isStableCoin($payload['Currency']);
      $appliedFraction = $isStable ? 0.0 : max(0.0, min(1.0, ($feePercent / 100.0)));
      $feeUsd = round($payload["TotalAmountUSD"] * $appliedFraction, 2);
      $payoutAmount = $this->computePayoutAmount($payload["TotalAmountUSD"], $range->rate, $payload['Currency'], $feePercent);

      return [
         'user_id' => $wallet->user->id,
         'reference' => Utils::generateReference(Prefix::CRYPTO),
         'address' => $wallet->address,
         'crypto' => $payload['Currency'],
         'crypto_amount' => $payload['TotalAmount'],
         'conversion_rate' => $range->rate,
         'asset_price' => $dollarPrice,
         'usd_value' => $payload["TotalAmountUSD"],
         'fee' => $feeUsd,
         'payout_amount' => $payoutAmount,
         'payout_currency' => 'NGN',
         'confirmations' => $confirmations,
         'status' => Status::SUCCESSFUL,
         'transaction_hash' => $payload['TxHashes'][0],
         'transaction_link' => "https://explorer.bitquery.io/search/{$payload['TxHashes'][0]}",
         'platform' => Tranx::XPROCESSING
      ];
   }

   /**
    * Compute NGN payout amount applying volatility fee for non-stable coins.
    */
   private function computePayoutAmount(float $usdValue, float $rate, string $symbol, float $feePercent = 0.0): float
   {
      $isStable = $this->isStableCoin($symbol);
      $fraction = max(0.0, min(1.0, ($feePercent / 100.0)));
      $appliedFee = $isStable ? 0.0 : $fraction;
      $netUsd = $usdValue * (1 - $appliedFee);
      return $rate * $netUsd;
   }

   /**
    * Basic stable coin detection (USDT/USDC across chains and aliases).
    */
   private function isStableCoin(string $symbol): bool
   {
      $s = strtoupper($symbol);
      return str_contains($s, 'USDT') || str_contains($s, 'USDC') || $s === 'TETHER';
   }

   /**
    * confirm transaction doest exist for users before processing
    */
   public function clientHasTransaction(array $payload)
   {
      return CryptoTransaction::where('user_id', $payload["ClientId"])
         ->where('transaction_hash', $payload['TxHashes'][0])->exists();
   }

   /**
    * Handle webhook reversal
    */
   public function webhook(array $payload)
   {
      $wallet = $this->cryptoRepo->chainWallet($payload["ClientId"], $payload["Currency"]);

      if ($this->clientHasTransaction($payload)) {
         return;
      }

      DB::beginTransaction();
      try {
         $tranx = CryptoTransaction::create($this->cryptoTranxPayload($wallet, $payload));

         $this->credit($tranx->user_id, $tranx->payout_amount);
         $tranxPayload = $this->tranxPayload($tranx);

         WalletTransaction::create($tranxPayload);
         $tranx->user->notify(new CryptoCompleteNotification($tranx));

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th;
      }
   }


   /**
    * The http client with auth credentials
    *
    * @return \Illuminate\Support\Facades\Http
    */
   public function http()
   {
      return Http::xprocess();
   }
}
