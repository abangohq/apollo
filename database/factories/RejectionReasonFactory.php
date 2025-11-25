<?php

namespace Database\Factories;

use App\Models\RejectionReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RejectionReason>
 */
class RejectionReasonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RejectionReason::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reasons = [
            'Insufficient documentation',
            'Invalid document format',
            'Document not clear',
            'Information mismatch',
            'Expired document',
            'Suspicious activity detected',
            'Incomplete information',
            'Document verification failed'
        ];

        return [
            'reason' => fake()->randomElement($reasons),
            'type' => fake()->randomElement(['kyc', 'withdrawal', 'transaction']),
        ];
    }

    /**
     * Indicate that this is a KYC rejection reason.
     */
    public function kyc(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kyc',
            'reason' => fake()->randomElement([
                'Insufficient documentation',
                'Invalid document format',
                'Document not clear',
                'Information mismatch',
                'Expired document'
            ]),
        ]);
    }

    /**
     * Indicate that this is a withdrawal rejection reason.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
            'reason' => fake()->randomElement([
                'Insufficient funds',
                'Invalid bank details',
                'Suspicious activity detected',
                'Daily limit exceeded',
                'Account verification required'
            ]),
        ]);
    }
}