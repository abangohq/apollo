<?php

namespace Database\Factories;

use App\Models\Withdrawal;
use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Withdrawal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bankAccount = BankAccount::factory()->create();
        
        return [
            'user_id' => User::factory(),
            'bank_id' => $bankAccount->bank_id,
            'bank_code' => $bankAccount->bank_code,
            'bank_name' => $bankAccount->bank_name,
            'account_name' => $bankAccount->account_name,
            'account_number' => $bankAccount->account_number,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['pending', 'approved', 'declined', 'processing']),
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'channel' => $this->faker->randomElement(['automated', 'manual']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}