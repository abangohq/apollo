<?php

namespace Database\Factories;

use App\Models\Kyc;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kyc>
 */
class KycFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Kyc::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'verification_type' => $this->faker->randomElement(['bvn', 'nin', 'passport', 'drivers_license']),
            'verification_data' => json_encode([
                'document_number' => $this->faker->numerify('###########'),
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'date_of_birth' => $this->faker->date(),
            ]),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the KYC is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the KYC is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'verified_at' => null,
        ]);
    }
}