<?php

namespace Database\Factories;

use App\Models\AirtimeTopUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AirtimeTopUp>
 */
class AirtimeTopUpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AirtimeTopUp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $networks = ['MTN', 'GLO', 'AIRTEL', '9MOBILE'];
        $amounts = [100, 200, 500, 1000, 2000, 5000];

        return [
            'user_id' => User::factory(),
            'product' => $this->faker->randomElement($networks),
            'phone_no' => $this->faker->regexify('0[789][01]\d{8}'),
            'amount_requested' => $this->faker->randomElement($amounts),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}