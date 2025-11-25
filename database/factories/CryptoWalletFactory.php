<?php

namespace Database\Factories;

use App\Models\CryptoWallet;
use App\Models\User;
use App\Models\CryptoAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CryptoWallet>
 */
class CryptoWalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CryptoWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'crypto_asset_id' => CryptoAsset::factory(),
            'address' => $this->faker->regexify('[a-zA-Z0-9]{34}'),
            'balance' => $this->faker->randomFloat(8, 0, 10),
            'chain' => $this->faker->randomElement(['BTC', 'ETH', 'BSC']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}