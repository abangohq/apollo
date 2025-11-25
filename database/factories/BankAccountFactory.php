<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bank = Bank::factory()->create();
        
        return [
            'user_id' => User::factory(),
            'bank_id' => $bank->id,
            'bank_code' => $bank->bank_code,
            'bank_name' => $bank->bank_name,
            'account_number' => $this->faker->numerify('##########'),
            'account_name' => $this->faker->name(),
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}