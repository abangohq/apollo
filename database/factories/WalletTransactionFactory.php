<?php

namespace Database\Factories;

use App\Models\WalletTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WalletTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'transaction_type' => $this->faker->randomElement(['App\\Models\\CryptoTransaction', 'App\\Models\\Withdrawal']),
            'transaction_id' => $this->faker->uuid(),
            'entry' => $this->faker->randomElement(['credit', 'debit']),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'narration' => $this->faker->sentence(),
            'currency' => 'NGN',
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'charge' => $this->faker->randomFloat(2, 0, 100),
            'total_amount' => function (array $attributes) {
                return $attributes['amount'] + ($attributes['charge'] ?? 0);
            },
            'wallet_balance' => $this->faker->randomFloat(2, 100, 10000),
            'is_reversal' => false,
            'mode' => $this->faker->randomElement(['online', 'offline']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}