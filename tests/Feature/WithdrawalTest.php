<?php

namespace Tests\Feature;

use App\Enums\Tranx;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\SystemSetting;
use App\Models\SystemStatus;
use App\Models\Tier;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\TestSetupTrait;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase, TestSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->setupTiers();
    }

    public function test_user_can_initiate_withdrawal_with_valid_bank_account()
    {
        // Mock the RedbillerService to prevent actual API calls
        $mockRedbillerService = $this->createMock(\App\Services\Payment\RedbillerService::class);
        $mockRedbillerService->method('transfer')
                            ->willReturn((object) [
                                'response' => 200,
                                'meta' => (object) ['status' => 'Pending'],
                                'details' => (object) ['reference' => 'REF123']
                            ]);
        $this->app->instance(\App\Services\Payment\RedbillerService::class, $mockRedbillerService);

        $this->setupWithdrawalEnvironment();

        $user = User::factory()->create([
            'pin' => bcrypt('1234'),
            'tier_id' => 1
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000.00
        ]);

        $bank = Bank::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/withdraw', [
            'amount' => 1000.00,
            'pin' => '1234',
            'bank_id' => $bankAccount->id
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'successful'
                 ]);
    }

    public function test_user_cannot_withdraw_more_than_balance()
    {
        // Mock the RedbillerService to prevent actual API calls
        $mockRedbillerService = $this->createMock(\App\Services\Payment\RedbillerService::class);
        $this->app->instance(\App\Services\Payment\RedbillerService::class, $mockRedbillerService);

        $this->setupWithdrawalEnvironment();

        $user = User::factory()->create([
            'pin' => bcrypt('1234'),
            'tier_id' => 1
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 500.00 // Less than withdrawal amount
        ]);

        $bank = Bank::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/withdraw', [
            'amount' => 1000.00,
            'pin' => '1234',
            'bank_id' => $bankAccount->id
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }

    public function test_user_cannot_withdraw_with_wrong_pin()
    {
        // Mock the RedbillerService to prevent actual API calls
        $mockRedbillerService = $this->createMock(\App\Services\Payment\RedbillerService::class);
        $this->app->instance(\App\Services\Payment\RedbillerService::class, $mockRedbillerService);

        $this->setupWithdrawalEnvironment();

        $user = User::factory()->create([
            'pin' => bcrypt('1234'),
            'tier_id' => 1
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000.00
        ]);

        $bank = Bank::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/withdraw', [
            'amount' => 1000.00,
            'pin' => '5678', // Wrong PIN
            'bank_id' => $bankAccount->id
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['pin']);
    }

    public function test_user_cannot_withdraw_with_invalid_bank_account()
    {
        // Mock the RedbillerService to prevent actual API calls
        $mockRedbillerService = $this->createMock(\App\Services\Payment\RedbillerService::class);
        $this->app->instance(\App\Services\Payment\RedbillerService::class, $mockRedbillerService);

        $this->setupWithdrawalEnvironment();

        $user = User::factory()->create([
            'pin' => bcrypt('1234'),
            'tier_id' => 1
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000.00
        ]);

        // Create a bank account for a different user
        $otherUser = User::factory()->create();
        $bank = Bank::factory()->create();
        $otherUserBankAccount = BankAccount::factory()->create([
            'user_id' => $otherUser->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/withdraw', [
            'amount' => 1000.00,
            'pin' => '1234',
            'bank_id' => $otherUserBankAccount->id // Using another user's bank account
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Whoops something went wrong, The selected bank account is invalid or hasnt been added.',
                     'errors' => [
                         'bank_id' => [
                             'The selected bank account is invalid or hasnt been added.'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_view_withdrawal_history()
    {
        $user = User::factory()->create();
        
        // Create some withdrawals
        Withdrawal::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Tranx::TRANX_SUCCESS
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/transactions?type=withdraw');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id',
                                 'reference',
                                 'amount',
                                 'status',
                                 'created_at'
                             ]
                         ],
                         'current_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }

    public function test_user_total_withdrawn_amount_is_calculated_correctly()
    {
        $user = User::factory()->create();

        // Create successful withdrawals
        Withdrawal::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount' => 1000.00,
            'status' => Tranx::TRANX_SUCCESS
        ]);

        // Create a pending withdrawal (should not be counted)
        Withdrawal::factory()->create([
            'user_id' => $user->id,
            'amount' => 500.00,
            'status' => 'pending'
        ]);

        $totalWithdrawn = $user->getTotalWithdrawnAttribute();

        $this->assertEquals(3000.00, $totalWithdrawn);
    }
}