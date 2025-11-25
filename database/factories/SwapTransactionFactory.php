<?php

namespace Database\Factories;

use App\Models\SwapTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SwapTransaction>
 */
class SwapTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SwapTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = ['BTC', 'ETH', 'USDT', 'BNB', 'ADA', 'DOT', 'LTC'];
        $fromCurrency = $this->faker->randomElement($currencies);
        $toCurrency = $this->faker->randomElement(array_diff($currencies, [$fromCurrency]));
        $fromAmount = $this->faker->randomFloat(8, 0.001, 10);
        $exchangeRate = $this->faker->randomFloat(8, 0.1, 5);

        return [
            'user_id' => User::factory(),
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'from_amount' => $fromAmount,
            'to_amount' => $fromAmount * $exchangeRate,
            'exchange_rate' => $exchangeRate,
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'cancelled']),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'refund_address' => $this->faker->regexify('[a-zA-Z0-9]{34}'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}