<?php

namespace App\Console\Commands;

use App\Models\CryptoAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchCryptoPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-crypto-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch real-time cryptocurrency prices and percentage price changes from CoinGecko.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $existing_assets = CryptoAsset::pluck('symbol')->toArray();
        $existing_assets = array_map(function($symbol) {
            return strpos($symbol, ' ') !== false ? strtoupper(explode(' ', $symbol)[0]) : strtoupper($symbol);
        }, $existing_assets);

        $response = Http::get('https://api.coingecko.com/api/v3/coins/markets', [
            'vs_currency' => 'usd',
            'order' => 'market_cap_desc',
            'per_page' => 250,
            'page' => 1,
            'sparkline' => false,
            'price_change_percentage' => '24h'
        ]);

        if ($response->successful()) {
            $coins = $response->json();

            foreach ($coins as $coin) {
                $symbol = strtoupper($coin['symbol']);        

                if (in_array($symbol, $existing_assets)) {
                    // Handle symbols like "USDT (ERC20)" by matching only the base symbol
                    CryptoAsset::whereRaw('UPPER(SUBSTRING_INDEX(symbol, " ", 1)) = ?', [$symbol])->update([
                        'price' => $coin['current_price'],
                        'latest_quote' => $coin['current_price'],
                        'percent_change_24hr' => $coin['price_change_percentage_24h'],
                        // 'logo' => $coin['image'],
                        'market_cap' => $coin['market_cap'],
                        'total_supply' => $coin['total_supply'],
                        'circulating_supply' => $coin['circulating_supply'],
                        'volume' => $coin['total_volume'],
                    ]);
                }
            }

            $this->info('Successfully fetched and updated cryptocurrency prices.');
        } else {
            $this->error('Failed to fetch cryptocurrency prices from CoinGecko.');
        }
    }
}
