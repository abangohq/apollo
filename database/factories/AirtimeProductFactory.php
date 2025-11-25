<?php

namespace Database\Factories;

use App\Models\AirtimeProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AirtimeProduct>
 */
class AirtimeProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AirtimeProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $networks = ['MTN', 'GLO', 'AIRTEL', '9MOBILE'];
        $amounts = [100, 200, 500, 1000, 2000, 5000, 10000];
        $minAmount = $this->faker->randomElement($amounts);
        $maxAmount = $minAmount * 10;

        return [
            'product' => $this->faker->randomElement($networks),
            'code' => strtolower($this->faker->randomElement($networks)),
            'logo' => $this->faker->imageUrl(100, 100, 'business'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'minimum_amount' => $minAmount,
            'maximum_amount' => $maxAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}