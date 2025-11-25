<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoinGeckoService
{
   public function __construct()
   {
      //
   }

   /**
    * Retrieve crpypto price from coingecko
    */
   public function cryptoPrice($cryptoId)
   {
      $cacheKey = "gecko_price:{$cryptoId}";

      try {
         // Primary: simple price endpoint with retry
         $response = $this->http()->retry(2, 200)->get('simple/price', [
            'ids' => $cryptoId,
            'vs_currencies' => 'usd',
         ]);

         $price = data_get($response->json(), "{$cryptoId}.usd");

         if (is_numeric($price) && floatval($price) > 0) {
            Cache::put($cacheKey, floatval($price), now()->addMinutes(30));
            return floatval($price);
         }

         // Fallback: markets endpoint
         $fallbackData = $this->getCryptoData($cryptoId);
         $fallbackPrice = is_array($fallbackData) ? ($fallbackData['price'] ?? null) : null;

         if (is_numeric($fallbackPrice) && floatval($fallbackPrice) > 0) {
            Cache::put($cacheKey, floatval($fallbackPrice), now()->addMinutes(30));
            return floatval($fallbackPrice);
         }

         // Last-good cached price to avoid propagating zeros
         $cached = Cache::get($cacheKey, 0);
         if (is_numeric($cached) && floatval($cached) > 0) {
            Log::warning('CoinGecko primary and fallback failed; using cached price', ['id' => $cryptoId, 'price' => $cached]);
            return floatval($cached);
         }

         // As a final guard, return 0 (caller may have separate fallback)
         return 0;
      } catch (\Throwable $e) {
         // On exception, try cache, otherwise 0
         $cached = Cache::get($cacheKey, 0);
         if (is_numeric($cached) && floatval($cached) > 0) {
            Log::warning('CoinGecko error; using cached price', ['id' => $cryptoId, 'price' => $cached, 'error' => $e->getMessage()]);
            return floatval($cached);
         }
         Log::error('CoinGecko price fetch failed with no cache available', ['id' => $cryptoId, 'error' => $e->getMessage()]);
         return 0;
      }
   }

   public function getCryptoData($cryptoId)
   {
      $response = $this->http()->get("coins/markets", [
         'vs_currency' => 'usd',
         'ids' => $cryptoId,
         'order' => 'market_cap_desc',
         'per_page' => 1,
         'page' => 1,
         'sparkline' => false,
      ]);

      $graphResponse = $this->http()->get("coins/{$cryptoId}/market_chart?vs_currency=usd&days=1");

      if ($graphResponse->ok()) {
         $graphResponse = array_slice($graphResponse['prices'], 0, 24);
         foreach ($graphResponse as $price) {
            $price_graph_data_points[] = [
               $price[1]
            ];
         }
         $price_graph_data_points = array_merge(...$price_graph_data_points);
      } else {
         $price_graph_data_points = [];
      }


      if ($response->ok()) {
         $data = $response->json();
         if (!empty($data) && count($data) > 0) {
            $returnArray = [
               'price' => $data[0]['current_price'],
               'market_cap' => $data[0]['market_cap'],
               'total_supply' => $data[0]['total_supply'],
               'circulating_supply' => $data[0]['circulating_supply'],
               'volume' => $data[0]['total_volume'],
               'percent_change_24hr' => $data[0]['price_change_percentage_24h'],
               'price_graph_data_points' => $price_graph_data_points,
               'last_updated' => now()->parse($data[0]['last_updated']),
            ];

            if (empty($price_graph_data_points)) {
               unset($returnArray['price_graph_data_points']);
            }

            return $returnArray;
         }
      }

      return $response->json();
   }

   /**
    * The http client with auth credentials
    *
    * @return \Illuminate\Support\Facades\Http
    */
   public function http()
   {
      return Http::gecko();
   }
}
