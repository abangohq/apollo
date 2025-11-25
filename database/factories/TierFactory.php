<?php

namespace Database\Factories;

use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tier>
 */
class TierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Standard', 'Premium', 'VIP']),
            'title' => fake()->sentence(3),
            'withdrawal_limit' => fake()->numberBetween(10000, 1000000),
            'requirements' => [
                'kyc_level' => fake()->numberBetween(1, 3),
                'minimum_balance' => fake()->numberBetween(0, 50000),
                'documents_required' => fake()->randomElements(['bvn', 'nin', 'photo'], fake()->numberBetween(1, 3))
            ],
        ];
    }

    /**
     * Indicate that this is a basic tier.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'title' => 'Basic Tier - Get Started',
            'withdrawal_limit' => 50000,
            'requirements' => [
                'kyc_level' => 1,
                'minimum_balance' => 0,
                'documents_required' => ['phone']
            ],
        ]);
    }

    /**
     * Indicate that this is a standard tier.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard',
            'title' => 'Standard Tier - Enhanced Features',
            'withdrawal_limit' => 200000,
            'requirements' => [
                'kyc_level' => 2,
                'minimum_balance' => 10000,
                'documents_required' => ['bvn', 'phone']
            ],
        ]);
    }

    /**
     * Indicate that this is a premium tier.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'title' => 'Premium Tier - Full Access',
            'withdrawal_limit' => 1000000,
            'requirements' => [
                'kyc_level' => 3,
                'minimum_balance' => 50000,
                'documents_required' => ['bvn', 'nin', 'photo']
            ],
        ]);
    }
}