<?php

namespace Database\Factories;

use App\Models\CryptoTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CryptoTransaction>
 */
class CryptoTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CryptoTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'ADA', 'DOT', 'LTC'];
        $crypto = $this->faker->randomElement($cryptos);
        $amount = $this->faker->randomFloat(8, 0.001, 10);
        $rate = $this->faker->randomFloat(2, 20000, 80000); // NGN rate

        return [
            'user_id' => User::factory(),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'crypto' => $crypto,
            'crypto_amount' => $amount,
            'conversion_rate' => $rate,
            'usd_value' => $amount * ($rate / 1000), // Convert to USD
            'payout_amount' => (int)($amount * $rate),
            'payout_currency' => 'NGN',
            'confirmations' => $this->faker->numberBetween(0, 6),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed', 'cancelled']),
            'transaction_hash' => $this->faker->regexify('[a-fA-F0-9]{64}'),
            'transaction_link' => $this->faker->url(),
            'address' => $this->faker->regexify('[a-zA-Z0-9]{34}'),
            'platform' => $this->faker->randomElement(['vaultody', 'xprocessing']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}