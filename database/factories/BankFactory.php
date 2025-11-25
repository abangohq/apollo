<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Bank::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banks = [
            ['name' => 'Access Bank', 'code' => '044'],
            ['name' => 'Guaranty Trust Bank', 'code' => '058'],
            ['name' => 'First Bank of Nigeria', 'code' => '011'],
            ['name' => 'United Bank for Africa', 'code' => '033'],
            ['name' => 'Zenith Bank', 'code' => '057'],
            ['name' => 'Fidelity Bank', 'code' => '070'],
            ['name' => 'Union Bank of Nigeria', 'code' => '032'],
            ['name' => 'Sterling Bank', 'code' => '232'],
        ];

        $bank = $this->faker->randomElement($banks);

        return [
            'bank_name' => $bank['name'],
            'bank_code' => $bank['code'],
            'bank_logo' => $this->faker->imageUrl(100, 100, 'business'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}