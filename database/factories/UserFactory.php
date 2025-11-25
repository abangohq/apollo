<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'dob' => fake()->date(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'referral_code' => strtoupper(Str::random(8)),
            'device_token' => Str::random(64),
            'device_type' => fake()->randomElement(['android', 'ios']),
            'status' => 'active',
            'tier_id' => fake()->numberBetween(1, 3),
            'credits' => fake()->numberBetween(0, 1000),
            'avatar' => fake()->imageUrl(),
            'pin' => fake()->numerify('####'),
            'failed_login_attempts' => 0,
            'heard_about_us' => fake()->randomElement(['social_media', 'friend', 'advertisement', 'other']),
            'user_type' => 'user',
            'role' => 'accountant',
            'deleted_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'role' => 'marketer',
        ]);
    }

    /**
     * Indicate that the user is staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'staff',
            'role' => 'announcer',
        ]);
    }
}
