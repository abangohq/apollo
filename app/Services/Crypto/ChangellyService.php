<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use phpseclib3\Crypt\RSA;

class ChangellyService
{
   /**
    * Sign the payload for a request
    */
   private function signature($body)
   {
      $privateKeyData = hex2bin(config('services.changelly.privateKey'));

      /** @var string **/
      $privateKey = RSA::load($privateKeyData);

      $message = json_encode($body);
      openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
      return base64_encode($signature);
   }

   /**
    * Get currency swap estimate
    */
   public function floatEstimate($props)
   {
      $payload = [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "getExchangeAmount",
         "params" => [
            "from" => $props['from'],
            "to" => $props['to'],
            "amountFrom" => $props['amountFrom']
         ]
      ];

      return $this->http($payload)->post('', $payload)->json();
   }

   /**
    * Get fixed rate swap estimate
    */
   public function fixedEstimate($props)
   {
      $payload = [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "getFixRateForAmount",
         "params" => [
            "from" => $props['from'],
            "to" => $props['to'],
            "amountFrom" => $props['amountFrom']
         ]
      ];

      return $this->http($payload)->post('', $payload)->json();
   }

   /**
    * Get swap rates to show users
    */
   public function swapRates($props)
   {
      $floatRate = $this->floatEstimate($props);
      $fixedRate = $this->fixedEstimate($props);

      if (array_key_exists('error', $floatRate)) {
         throw ValidationException::withMessages(['from' => [$floatRate['error']['message']]]);
      }

      if (array_key_exists('error', $fixedRate)) {
         throw ValidationException::withMessages(['from' => [$fixedRate['error']['message']]]);
      }

      $floatRate = $floatRate['result'][0];
      $fixedRate = $fixedRate['result'][0];

      return [
         'exchange' => $props,
         'floating' => [
            'amountTo' => $floatRate['amountTo'] - $floatRate['networkFee'],
            'min' => $floatRate['min'],
            'max' => $floatRate['max'],
            'expiredAt' => null,
         ],
         'fixed' => [
            'rate_id' => $fixedRate['id'],
            'amountTo' => $fixedRate['amountTo'] - $fixedRate['networkFee'],
            'min' => $fixedRate['min'],
            'max' => $fixedRate['max'],
            'expiredAt' => now()->createFromTimestamp($fixedRate['expiredAt'], 'Africa/Lagos')->toDateTimeString()
         ]
      ];
   }

   /**
    * Create a floating rate transaction
    */
   public function createTransaction($props)
   {
      $payload =  [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "createTransaction",
         "params" => [
            "from" => $props['from'],
            "to" => $props['to'],
            "address" => $props['address'],
            "amountFrom" => $props['amountFrom']
         ]
      ];

      return $this->http($payload)->post('', $payload)->json();
   }

   /**
    * Create a fixed rate transaction
    */
   public function createFixedTransaction($props)
   {
      $payload =  [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "createFixTransaction",
         "params" => [
            "from" => $props['from'],
            "to" => $props['to'],
            "address" => $props['address'],
            "amountFrom" => $props['amountFrom'],
            "rateId" => $props['rateId'],
            "refundAddress" => $props['refundAddress'],
         ]
      ];

      return $this->http($payload)->post('', $payload)->json();
   }

   /**
    * Retrieve available currency for swap exchange
    */
   public function currencies()
   {
      $payload =  [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "getCurrenciesFull",
         "params" => []
      ];

      return Cache::remember('swap-currencies', now()->addMinutes(15), function () use ($payload) {
         $response = $this->http($payload)->post('', $payload)->json();

         // Extract only ticker and image from each currency
         if (isset($response['result']) && is_array($response['result'])) {
            $response['result'] = collect($response['result'])->map(function ($currency) {
               return [
                  'name' => $currency['name'] ?? null,
                  'fullName' => $currency['fullName'] ?? null,
                  'ticker' => $currency['ticker'] ?? null,
                  'image' => $currency['image'] ?? null,
               ];
            })->toArray();
         }

         return $response;
      });
   }

   /**
    * Retrieve available currency swap pairs
    */
   public function swapPairs($from)
   {
      $payload =  [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "getPairs",
         "params" => ['from' => $from]
      ];

      return Cache::remember("swap-{$from}", now()->addMinutes(5), function () use ($payload) {
         return $this->http($payload)->post('', $payload)->json();
      });
   }

   /**
    * Get transaction status for a swap transaction
    */
   public function swapStatus(string $swapTx)
   {
      $payload =  [
         "jsonrpc" => "2.0",
         "id" => config('services.changelly.id'),
         "method" => "getStatus",
         "params" => [
            "id" => $swapTx
         ]
      ];

      return $this->http($payload)->post('', $payload)->json();
   }

   /**
    * The http client with auth credentials
    *
    * @return \Illuminate\Support\Facades\Http
    */
   public function http($payload)
   {
      $signature = $this->signature($payload);

      return Http::changelly()->withHeaders([
         'X-Api-Signature' => $signature
      ]);
   }
}
