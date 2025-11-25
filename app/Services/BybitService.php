<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BybitService
{
    protected $apiKey;
    protected $secretKey;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->apiKey = config('services.bybit.api_key');
        $this->secretKey = config('services.bybit.secret_key');
        $this->baseUrl = config('services.bybit.base_url', 'https://api.bytick.com');
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    /**
     * Create a spot trade order on Bybit
     *
     * @param string $symbol Trading pair (e.g. 'BTCUSDT')
     * @param string $side 'Buy' or 'Sell'
     * @param string $orderType 'Market', 'Limit', etc.
     * @param float $quantity Amount to buy/sell
     * @param float|null $price Price for limit orders (null for market orders)
     * @return array Response from Bybit API
     */
    public function createSpotTrade(string $symbol, string $side, string $orderType, float $quantity, ?float $price = null)
    {
        $timestamp = time() * 1000;
        $recvWindow = "5000";

        $params = [
            'category' => 'spot',
            'symbol' => $symbol,
            'side' => $side,
            'orderType' => $orderType,
            'qty' => (string) $quantity,
            'timestamp' => $timestamp,
            'recvWindow' => $recvWindow,
        ];

        if ($orderType === 'Limit' && $price !== null) {
            $params['price'] = (string) $price;
        }

        // Signature
        $queryString = json_encode($params, JSON_UNESCAPED_SLASHES);
        $params_for_signature = $timestamp . $this->apiKey . $recvWindow . $queryString;
        $signature = hash_hmac('sha256', $params_for_signature, $this->secretKey);

        try {
            $response = $this->client->post('/v5/order/create', [
                'headers' => [
                    'X-BAPI-API-KEY' => $this->apiKey,
                    'X-BAPI-SIGN' => $signature,
                    'X-BAPI-TIMESTAMP' => $timestamp,
                    'X-BAPI-SIGN-TYPE' => '2',
                    'X-BAPI-RECV-WINDOW' => $recvWindow,
                    'Content-Type' => 'application/json',
                ],
                'json' => $params,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!empty($result)) {
                \DB::table('bybit_trades')->insert([
                    'id' => (string) \Str::orderedUuid(),
                    'symbol' => $symbol,
                    'side' => $side,
                    'order_type' => $orderType,
                    'quantity' => $quantity,
                    'price' => $price,
                    'response' => json_encode($result),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $result;

        } catch (\Throwable $e) {
            \Log::error('Failed to create Bybit spot trade', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $params,
            ]);

            throw new \Exception('Failed to create spot trade: ' . $e->getMessage());
        }
    }

}