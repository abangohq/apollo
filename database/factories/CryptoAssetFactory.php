<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CryptoAsset>
 */
class CryptoAssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CryptoAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cryptos = [
            ['name' => 'Bitcoin', 'symbol' => 'BTC', 'network' => 'Bitcoin', 'decimals' => 8],
            ['name' => 'Ethereum', 'symbol' => 'ETH', 'network' => 'Ethereum', 'decimals' => 18],
            ['name' => 'Tether USD', 'symbol' => 'USDT', 'network' => 'Ethereum', 'decimals' => 6],
            ['name' => 'Binance Coin', 'symbol' => 'BNB', 'network' => 'BSC', 'decimals' => 18],
            ['name' => 'Cardano', 'symbol' => 'ADA', 'network' => 'Cardano', 'decimals' => 6],
            ['name' => 'Polkadot', 'symbol' => 'DOT', 'network' => 'Polkadot', 'decimals' => 10],
            ['name' => 'Litecoin', 'symbol' => 'LTC', 'network' => 'Litecoin', 'decimals' => 8],
        ];

        $crypto = $this->faker->randomElement($cryptos);

        return [
            'name' => $crypto['name'],
            'symbol' => $crypto['symbol'],
            'status' => 'active',
            'logo' => $crypto['symbol'] === 'BTC' ? 'btc-logo.png' : $this->faker->imageUrl(64, 64, 'business'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}