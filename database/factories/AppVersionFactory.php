<?php

namespace Database\Factories;

use App\Models\AppVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppVersion>
 */
class AppVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AppVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_number' => fake()->semver(),
            'release_date' => fake()->date(),
            'message' => fake()->sentence(10),
        ];
    }

    /**
     * Indicate that this is a current version.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'release_date' => now()->toDateString(),
            'message' => 'Current stable version with latest features and bug fixes.',
        ]);
    }

    /**
     * Indicate that this is a beta version.
     */
    public function beta(): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => fake()->semver() . '-beta',
            'message' => 'Beta version for testing new features.',
        ]);
    }
}