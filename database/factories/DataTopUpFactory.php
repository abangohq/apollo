<?php

namespace Database\Factories;

use App\Models\DataTopUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataTopUp>
 */
class DataTopUpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DataTopUp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $networks = ['MTN', 'GLO', 'AIRTEL', '9MOBILE'];
        $plans = [
            ['id' => 'mtn-1gb', 'name' => '1GB Monthly', 'amount' => 350],
            ['id' => 'mtn-2gb', 'name' => '2GB Monthly', 'amount' => 700],
            ['id' => 'glo-1.5gb', 'name' => '1.5GB Monthly', 'amount' => 500],
            ['id' => 'airtel-1gb', 'name' => '1GB Monthly', 'amount' => 400],
        ];

        $plan = $this->faker->randomElement($plans);

        return [
            'user_id' => User::factory(),
            'product' => $this->faker->randomElement($networks),
            'phone_no' => $this->faker->regexify('0[789][01]\d{8}'),
            'name' => $plan['name'],
            'code' => $plan['id'],
            'amount_requested' => $plan['amount'],
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}