<?php

namespace App\Services\Crypto;

use App\Enums\CoinEventType;
use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\CryptoAsset;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoWalletAddressHistory;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\User\CryptoCompleteNotification;
use App\Notifications\User\CryptoPendingNotification;
use App\Services\SignUpBonusService;
use App\Repositories\CryptoRepository;
use App\Services\BybitService;
use App\Support\Utils;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RangeException;

class VaultodyService
{
   use WalletEntity;

   /**
    * Get the vaultody master walletId
    *
    *  @var string   $walletId
    */
   public $walletId = null;

   /**
    * Get the chain for the action
    *
    * @var string $chain
    */
   public $chain = null;

   /**
    * Get the network for this chain
    *
    * @var string $network
    */
   public $network = null;

   public function __construct(public CryptoRepository $cryptoRepo, public CoinGeckoService $coinGeckoService)
   {
      $this->walletId = config('services.vaultody.wallet_id');
      $this->chain = Tranx::BITCOIN->value;
      $this->network = app()->environment('local') ? 'testnet' : 'mainnet';
   }

   /**
    * Get wallet asset details
    */
   public function getWalletAssetDetails()
   {
      $net = app()->environment('local') ? 'TEST' : 'MAIN';

      $path = "/assets/{$net}";

      $response = $this->http([], 'GET', $path)->get($path, []);

      if ($response->ok()) {
          $assets = [];
          foreach ($response->json('data.items') as $item) {
            $assets[] = [
               'balance' => $item['availableAmount'],
               'symbol' => $item['symbol']
            ];
          }
          return $assets;
      }else{
         throw new \Exception(json_encode($response->object()));
      }
   }

   /**
    * Vaultody priority fee
    */
   public function getFeeEstimate()
   {
      $path = "/blockchain-data/{$this->chain}/{$this->network}/mempool/fees";
      $request = $this->http([], 'GET', $path)->get($path, []);

      if ($request->ok()) {
         return $request->object();
      } else {
         throw new \Exception(json_encode($request->object()));
      }
   }

   /**
    * Retrieve wallet addresses
    */
   public function getAddresses()
   {
      $path = "/wallet-as-a-service/wallets/{$this->walletId}/{$this->chain}/{$this->network}/addresses";
      $request = $this->http([], 'GET', $path)->get($path, []);

      if ($request->ok()) {
         return $request->object();
      } else {
         throw new \Exception(json_encode($request->object()));
      }
   }

   /**
    * Retrieve fees estimate
    */
   public function estimate()
   {
      $response = $this->getFeeEstimate();
      $addresses = $this->getAddresses();

      $input_count = 0;

      // Iterate over the items
      foreach ($addresses->data->items as $item) {
         // Check if the confirmed balance is greater than 0
         if (floatval($item->confirmedBalance->amount) > 0) {
            $input_count++;
         }
      }

      $output_count = 1;
      $multiplier = (160 + $input_count * 41 + $output_count * 34);

      $response->data->item->fast *= $multiplier;
      $response->data->item->slow *= $multiplier;
      $response->data->item->standard *= $multiplier;

      return $response;
   }

   /**
    * Iniatiate wallet withdrawal
    */
   public function walletWithdrawal(array $payload)
   {
      $walletId = config('services.vaultody.wallet_id');
      $net = app()->environment('local') ? 'testnet' : 'mainnet';
      $chain = Tranx::BITCOIN->value;

      $body = [
         "data" =>  [
               "item" => [
                  'feePriority' => $payload['priority'],
                  'prepareStrategy' => 'optimize-size',
                  'recipients' => [
                     [
                        "address" => $payload['address'],
                        "amount" => $payload['amount']
                     ]
                  ]
               ]
            ]
      ];

      $path = "/vaults/{$walletId}/{$chain}/{$net}/transaction-requests";

      $response = $this->http($body, 'POST', $path)->post($path, $body)->object();

      return $response;
   }

   /**
    * Generate deposit address using the master wallet id
    */
   public function generateAddress($email, $chain)
   {
      $walletId = match ($chain) {
         Tranx::SOLANA->value, Tranx::BITCOIN->value, Tranx::XRP->value => config('services.vaultody.vault_id'),
         default => config('services.vaultody.wallet_id'),
      };


      $net = match ($chain) {
         Tranx::BITCOIN->value => app()->environment('local') ? 'testnet' : 'mainnet',
         Tranx::ETHEREUM->value => app()->environment('local') ? 'sepolia' : 'mainnet',
         Tranx::TRON->value => app()->environment('local') ? 'nile' : 'mainnet',
         Tranx::SOLANA->value => app()->environment('local') ? 'testnet' : 'mainnet',
         Tranx::XRP->value => app()->environment('local') ? 'testnet' : 'mainnet',
         default => throw new \Exception('Unsupported chain'),
      };

      $body = [
         "data" =>  [
            "item" => [
               "label" => $email,
            ]
         ]
      ];

      $path = "/vaults/{$walletId}/{$chain}/{$net}/addresses";

      $wallet = $this->http($body, 'POST', $path)->post($path, $body);

      if ($wallet->ok()) {
         return $wallet->object();
      } else {
         throw new \Exception(json_encode($wallet->object()));
      }
   }

   /**
    * Create app crypto wallet for user with crypto address
    *
    * @return \Illuminate\Database\Eloquent\Model|null;
    */
   public function createWallet(CryptoAsset $asset, User $user)
   {
      // Enforce wallet creation limit: non–Tier 2 users max 2 wallets
      if (($user->tier_id ?? null) !== 2) {
         $walletCount = CryptoWallet::where('user_id', $user->id)->count();
         if ($walletCount >= 2) {
            abort(403, 'You can only create up to 2 crypto wallets unless you complete your KYC verification.');
         }
      }

      $chain = match ($asset->symbol) {
         'BTC' => Tranx::BITCOIN->value,
         'ETH' => Tranx::ETHEREUM->value,
         'USDT (ERC20)' => Tranx::ETHEREUM->value,
         'USDT (TRC20)' => Tranx::TRON->value,
         'SOL' => Tranx::SOLANA->value,
         'XRP' => Tranx::XRP->value,
         default => throw new \Exception('Unsupported asset symbol'),
      };
      if ($asset->symbol === 'USDT (ERC20)') {
         $ethWallet = CryptoWallet::where('user_id', $user->id)
         ->where('chain', 'ETH')
         ->first();

         if ($ethWallet) {
         return CryptoWallet::create([
            'chain' => $asset->symbol,
            'address' => $ethWallet->address,
            'crypto_asset_id' => $asset->id,
            'user_id' => $user->id
         ]);
         }
      }

      if ($asset->symbol === 'ETH') {
         $usdtWallet = CryptoWallet::where('user_id', $user->id)
         ->where('chain', 'USDT (ERC20)')
         ->first();

         if ($usdtWallet) {
         return CryptoWallet::create([
            'chain' => $asset->symbol,
            'address' => $usdtWallet->address,
            'crypto_asset_id' => $asset->id,
            'user_id' => $user->id
         ]);
         }
      }

      $response = $this->generateAddress($user->email, $chain);
      logger()->info('Generated Address Response:', (array) $response);

      return CryptoWallet::create([
         'chain' => $asset->symbol,
         'address' => $response->data->item->address,
         'crypto_asset_id' => $asset->id,
         'user_id' => $user->id
      ]);
   }

   /**
    * Fetch transaction transaction
    */
    public function getTransaction(string $transaction_id)
    {
      $walletId = config('services.vaultody.wallet_id');
      $chain = Tranx::BITCOIN->value;

      $path = "/vaults/{$walletId}/{$chain}/transactions/{$transaction_id}";
      $tranx = $this->http([], 'GET', $path)->get($path)->object();

      return $tranx;
    }

   /**
    * Get the reversal transaction payload
    */
   public function walletTranxPayload(CryptoTransaction $transaction, Status $status)
   {
      return [
         // 'user_id' => $transaction->user_id,
         // 'transaction_id' => $transaction->id,
         'reference' => $transaction->reference,
         'transaction_type' => Tranx::CRYPTO,
         'entry' => Tranx::CREDIT,
         'status' => $status,
         'narration' => "{$transaction->payout_currency} {$transaction->payout_amount} {$transaction->crypto} payout",
         'amount' => $transaction->payout_amount,
         'charge' => 0,
         'total_amount' => $transaction->payout_amount,
         'wallet_balance' => $transaction->user->wallet()->value('balance'),
      ];
   }

   /**
    * Get the crypto transaction Payload
    */
   public function cryptoTranxPayload(CryptoWallet $wallet, object $payload, mixed $dollarPrice, Status $status)
   {
      $cryptoAmount = $payload->item->amount ?? $payload->item->token->tokensAmount;
      $usdValue = $cryptoAmount * $dollarPrice;

      $range = $this->cryptoRepo->rateRange($usdValue);

      if (is_null($range)) {
         throw new RangeException('dollar price not in range.');
      }

      $confirmations = $payload->item->currentConfirmations;

      $usdValue = $cryptoAmount * $dollarPrice;

      $chain = $wallet->chain;

      $transactionLink = match ($chain) {
         'SOL', 'USDT (SOLANA)' => "https://solscan.io/account/{$payload->item->address}",
         'USDT (TRC20)' => "https://tronscan.org/#/address/{$payload->item->address}",
         'XRP' => "https://xrpscan.com/account/{$payload->item->address}",
         default => "https://blockexplorer.one/{$payload->item->blockchain}/{$payload->item->network}/address/{$payload->item->address}",
      };

      $feePercent = (float) ($range->fee ?? 0);
      $isStable = $this->isStableCoin($chain);
      $appliedFraction = $isStable ? 0.0 : max(0.0, min(1.0, ($feePercent / 100.0)));
      $feeUsd = round($usdValue * $appliedFraction, 2);

      return [
         // 'user_id' => $wallet->user->id,
         // 'transaction_hash' => $payload->item->transactionId,
         'reference' => Utils::generateReference(Prefix::CRYPTO),
         'address' => $payload->item->address,
         'crypto' => $chain,
         'crypto_amount' => $cryptoAmount,
         'conversion_rate' => $range->rate,
         'asset_price' => $dollarPrice,
         'usd_value' => $usdValue,
         'fee' => $feeUsd,
         'payout_amount' => $this->computePayoutAmount($usdValue, $range->rate, $chain, $feePercent),
         'payout_currency' => 'NGN',
         'confirmations' => $confirmations,
         'status' => $status,
         'transaction_link' => $transactionLink,
         'platform' => Tranx::VAULTODY
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
    * check if there is already a third confirmation
    */
   public function hasThreeConfirmations(CryptoWallet $wallet, object $payload)
   {
      $transaction = CryptoTransaction::query()->where('transaction_hash', $payload->item->transactionId)
         ->where('user_id', $wallet->user_id)->first();

      if (is_null($transaction)) {
         return false;
      }

      return intval($transaction->confirmations) < 3 ? false : true;
   }

   public function getFallbackPrice($blockchain)
   {
      $lastTransaction = CryptoTransaction::where('crypto', $blockchain)
         ->where('asset_price', '>', 0)
         ->orderBy('created_at', 'desc')
         ->first();

      if ($lastTransaction && $lastTransaction->asset_price > 0) {
         return $lastTransaction->asset_price;
      }
   }

   /**
    * Handle fulfillment we will check update or create
    * and only credit if no existing 3 confirmations
    */
   public function webhook(array $incoming)
   {
      $payload = json_decode(json_encode($incoming['data']), false);

      $idempotencyKey = json_decode(json_encode($incoming), false)->idempotencyKey;

      // Check if this webhook has already been processed
      if (Cache::has($idempotencyKey)) {
         Log::info('Webhook already processed (idempotency check)', [
            'transaction_id' => $payload->item->transactionId,
            'address' => $payload->item->address,
            'event' => $payload->event,
            'idempotency_key' => $idempotencyKey
         ]);
         return;
      }

      // Set idempotency lock for 1 hour
      Cache::put($idempotencyKey, true, 3600);

      if (!in_array($payload->event, ['INCOMING_CONFIRMED_COIN_TX', 'INCOMING_CONFIRMED_TOKEN_TX'])) {
         return;
      }

      // Ignore transactions with failed status
      if (isset($payload->item->status) && $payload->item->status === 'failed') {
         return;
      }

      $wallet = CryptoWallet::where('address', $payload->item->address)->orWhere('old_address', $payload->item->address)->with('user')->first();

      // If wallet not found, check CryptoWalletAddressHistory
      if (!$wallet) {
         $addressHistory = CryptoWalletAddressHistory::where('old_address', $payload->item->address)
            ->orWhere('new_address', $payload->item->address)
            ->first();

         if ($addressHistory) {
            $wallet = CryptoWallet::where('id', $addressHistory->crypto_wallet_id)->with('user')->first();
         }
      }

      // Skip processing if crypto amount rounded to 8dp is zero
      $rawAmount = $payload->item->amount ?? ($payload->item->token->tokensAmount ?? null);
      if (!is_null($rawAmount)) {
         $amount8dp = number_format((float) $rawAmount, 8, '.', '');
         if ($amount8dp === '0.00000000') {
            Log::info('Ignoring webhook due to zero crypto amount at 8dp', [
               'transaction_id' => $payload->item->transactionId ?? null,
               'address' => $payload->item->address ?? null,
               'event' => $payload->event ?? null,
               'raw_amount' => $rawAmount,
               'rounded_8dp' => $amount8dp,
            ]);
            return;
         }
      }

      $blockchain = match ($payload->event) {
         'INCOMING_CONFIRMED_TOKEN_TX' => match ($payload->item->tokenType) {
         'TRC-20', 'ERC-20', 'SPL' => 'tether',
         default => throw new \Exception('Unsupported token type'),
         },
         default => $payload->item->blockchain,
      };

      if($blockchain == 'xrp') {
         $blockchain = 'ripple';
      }

      $dollarPrice = $this->coinGeckoService->cryptoPrice($blockchain);

      if ($this->hasThreeConfirmations($wallet, $payload)) {
         return;
      }

      try {
         DB::beginTransaction();

         $status = $payload->item->currentConfirmations < 3 ? Status::PENDING : Status::SUCCESSFUL;

         $cryptoTranxPayload = $this->cryptoTranxPayload($wallet, $payload, $dollarPrice, $status);

         $cryptoTransaction = CryptoTransaction::query()->firstOrCreate([
            'transaction_hash' => $payload->item->transactionId,
            'user_id' => $wallet->user_id
         ], $cryptoTranxPayload);

         $walletTranxPayload = $this->walletTranxPayload($cryptoTransaction, $status);

         $walletTransaction = WalletTransaction::query()->firstOrCreate([
            'user_id' => $cryptoTransaction->user_id,
            'transaction_id' => $cryptoTransaction->id,
            'transaction_type' => Tranx::CRYPTO,
            'is_reversal' => false
         ], $walletTranxPayload);

         if ($status->value == Status::SUCCESSFUL->value) {

            if (isset($cryptoTransaction->payout_amount) && $cryptoTransaction->payout_amount == 0) {

               $dollarPrice = $this->getFallbackPrice($cryptoTransaction->crypto);
               $usdValue = $cryptoTransaction->crypto_amount * $dollarPrice;
               $range = $this->cryptoRepo->rateRange($usdValue);

               $cryptoTransaction->payout_amount = $this->computePayoutAmount($usdValue, $range->rate, $cryptoTransaction->crypto, (float) ($range->fee ?? 0));

               // Recompute payloads with fallback price
               $cryptoTranxPayload = $this->cryptoTranxPayload($wallet, $payload, $dollarPrice, $status);
            }

            // Persist updated payloads BEFORE crediting and ensure models are up-to-date
            $updatedCryptoTranx = (bool) CryptoTransaction::where('id', $cryptoTransaction->id)
               ->update(collect($cryptoTranxPayload)->except('reference')->all());

            $cryptoTransaction->refresh();

            $walletTranxPayload = $this->walletTranxPayload($cryptoTransaction, $status);

            // Credit using the updated payout amount
            $this->credit($walletTransaction->user_id, $cryptoTransaction->payout_amount);

            $updatedWalletTranx = (bool) WalletTransaction::where('id', $walletTransaction->id)
               ->update(collect($walletTranxPayload)->except('reference')->all());

            // Update sign-up bonus trade volume
            $signUpBonusService = new SignUpBonusService();
            $signUpBonusService->updateTradeVolume($walletTransaction->user);

            $walletTransaction->user->notify(new CryptoCompleteNotification($cryptoTransaction));

            if($cryptoTransaction->crypto == 'BTC' || $cryptoTransaction->crypto == 'ETH') {
               $pair = $cryptoTransaction->crypto == 'BTC' ? 'BTCUSDT' : 'ETHUSDT';
               (new BybitService())->createSpotTrade(
                  $pair,
                  'Sell',
                  'Market',
                  number_format($cryptoTransaction->crypto_amount, 5, '.', '')
               );
            }

         } else {
            // For PENDING, persist latest payloads and notify
            $updatedCryptoTranx = (bool) CryptoTransaction::where('id', $cryptoTransaction->id)
               ->update(collect($cryptoTranxPayload)->except('reference')->all());

            $cryptoTransaction->refresh();

            $walletTranxPayload = $this->walletTranxPayload($cryptoTransaction, $status);

            $updatedWalletTranx = (bool) WalletTransaction::where('id', $walletTransaction->id)
               ->update(collect($walletTranxPayload)->except('reference')->all());

            $walletTransaction->user->notify(new CryptoPendingNotification($cryptoTransaction));
         }

         // throw_if(!$updatedCryptoTranx || !$updatedWalletTranx, 'unable to update crypto fulfillment.');

         DB::commit();
      } catch (\Throwable $th) {
         DB::rollBack();

         // Clear idempotency key on failure so webhook can be retried
         Cache::forget($idempotencyKey);

         Log::error('Webhook processing failed, cleared idempotency key', [
            'transaction_id' => $payload->item->transactionId,
            'address' => $payload->item->address,
            'error' => $th->getMessage(),
            'idempotency_key' => $idempotencyKey
         ]);

         throw $th;
      }
   }


   public function http($body = [], $method, $path, $query = [])
   {
       $timestamp = time();

       if (strtoupper($method) === 'GET') {
           $body = '{}';
       } else {
           $bodyJson = json_encode($body);

           $bodyJson = preg_replace('/\n(?=(?:[^"]*"[^"]*")*[^"]*$)/', '', $bodyJson);

           $bodyJson = preg_replace('/(".*?")|\s+/', '$1', $bodyJson);
           $body = $bodyJson;
       }


       if (empty($query)) {
           $transformedQuery = new \stdClass();
       } else {
           $transformedQuery = (object)$query;
       }

       $messageToSign = $timestamp . strtoupper($method) . $path . $body . json_encode($transformedQuery);

       $key = base64_decode(config('services.vaultody.secret'));
       $signature = base64_encode(
           hash_hmac('sha256', $messageToSign, $key, true)
       );
      //  dd(base64_decode(config('services.vaultody.secret')));

       $headers = [
           'Content-Type' => 'application/json',
           'x-api-key' => config('services.vaultody.key'),
           'x-api-sign' => $signature,
           'x-api-timestamp' => $timestamp,
           'x-api-passphrase' => config('services.vaultody.passphrase'),
       ];

       return Http::withHeaders($headers)->baseUrl(config('services.vaultody.url'));
   }

}
