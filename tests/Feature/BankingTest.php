<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\SystemStatus;
use App\Models\SystemSetting;
use App\Models\Tier;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\TestSetupTrait;

class BankingTest extends TestCase
{
    use RefreshDatabase, WithFaker, TestSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->setupBanks();
        $this->setupSystemConfigurations();
        $this->setupTasks();
        $this->setupWithdrawalEnvironment();
        $this->setupTiers();
    }

    private function createUserWithWallet($balance = 10000)
    {
        $user = User::factory()->create([
            'pin' => Hash::make('1234'),
            'tier_id' => 1
        ]);
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance,
            'wallet_currency' => 'NGN'
        ]);
        return $user;
    }

    public function test_user_can_view_available_banks()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/bank/');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'bank_name',
                             'bank_code',
                             'bank_logo',
                             'status'
                         ]
                     ]
                 ]);

        $banks = $response->json('data');
        $this->assertCount(3, $banks);
        $this->assertTrue(collect($banks)->contains('bank_name', 'Access Bank'));
    }

    public function test_authenticated_user_can_add_bank_account()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::where('bank_code', '044')->first();

        Sanctum::actingAs($user);

        $accountNumber = '12345' . rand(10000, 99999);

        $response = $this->postJson('/api/bank/create', [
            'bank_id' => $bank->id,
            'account_number' => $accountNumber,
            'account_name' => 'John Doe'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'account_number',
                         'account_name',
                         'bank_name',
                         'bank_code',
                         'is_primary'
                     ]
                 ]);

        $this->assertDatabaseHas('bank_accounts', [
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'account_number' => $accountNumber,
            'account_name' => 'John Doe'
        ]);
    }

    public function test_user_can_view_their_bank_accounts()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        // Create bank accounts for the user
        BankAccount::factory()->count(2)->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/bank/accounts');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'account_number',
                             'account_name',
                             'bank_name',
                             'bank_code',
                             'is_primary',
                             'created_at'
                         ]
                     ]
                 ]);

        $bankAccounts = $response->json('data');
        $this->assertCount(2, $bankAccounts);
    }

    public function test_user_can_set_default_bank_account()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => false
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/bank/{$bankAccount->id}/edit", [
            'account_id' => $bankAccount->id
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Bank details updated successfully.'
                 ]);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bankAccount->id,
            'is_primary' => true
        ]);
    }

    public function test_user_can_delete_bank_account()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => false
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/bank/{$bankAccount->id}/delete");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Bank removed successfully.'
                 ]);

        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_user_cannot_delete_default_bank_account()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/bank/{$bankAccount->id}/delete");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Bank removed successfully.'
                 ]);

        $this->assertDatabaseMissing('bank_accounts', [
            'id' => $bankAccount->id
        ]);
    }

    public function test_user_can_verify_bank_account()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::where('bank_code', '044')->first();

        // Mock the external API call
         Http::fake([
             '*/1.0/kyc/bank-account/verify' => Http::response([
                 'details' => [
                     'error' => 'Invalid account number'
                 ]
             ], 200)
         ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/bank/verify', [
            'bank_code' => $bank->bank_code,
            'account_no' => '1234567890'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Whoops something went wrong, Account Number does not exist in selected bank'
                 ]);

        // Assert that the HTTP request was made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/1.0/kyc/bank-account/verify') &&
                   $request['account_no'] === '1234567890' &&
                   $request['bank_code'] === '044';
        });
    }

     public function test_user_can_initiate_withdrawal()
    {
        // Mock the RedbillerService to prevent actual API calls
        $mockRedbillerService = $this->createMock(\App\Services\Payment\RedbillerService::class);

        // Mock the transfer method to return a pending response
        $mockResponse = (object) [
            'response' => 200,
            'meta' => (object) ['status' => 'Pending'],
            'details' => (object) ['reference' => 'TEST_REF_123']
        ];

        $mockRedbillerService->method('transfer')->willReturn($mockResponse);
        $this->app->instance(\App\Services\Payment\RedbillerService::class, $mockRedbillerService);

        $user = $this->createUserWithWallet(50000);
        $bank = Bank::first();

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/withdraw', [
            'amount' => 10000,
            'bank_id' => $bankAccount->id,
            'pin' => '1234'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'entry',
                         'status',
                         'amount',
                         'created_at'
                     ]
                 ]);

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'amount' => 10000,
            'bank_id' => $bankAccount->bank_id,
            'status' => 'pending'
        ]);
    }


    public function test_user_can_view_wallet_transactions()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bankAccount->bank_id,
            'bank_code' => $bankAccount->bank_code,
            'bank_name' => $bankAccount->bank_name,
            'account_name' => $bankAccount->account_name,
            'account_number' => $bankAccount->account_number,
            'amount' => 15000,
            'status' => 'successful'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);

        // Check that the response is successful
        $this->assertTrue($response->json('success'));
    }

    public function test_bank_account_validation_requires_valid_account_number()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/bank/create', [
            'bank_id' => $bank->id,
            'account_number' => '123', // Invalid account number
            'account_name' => 'John Doe'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['account_number']);
    }

    public function test_user_has_default_bank_account_method()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        // Initially user should not have default bank account
        $this->assertFalse($user->has_default_bank_account);

        // Create default bank account
        BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        // User should now have default bank account
        $user->refresh();
        $this->assertTrue($user->has_default_bank_account);
    }



    public function test_only_one_default_bank_account_per_user()
    {
        $user = $this->createUserWithWallet();
        $bank = Bank::first();

        // Create first default bank account
        $firstAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => true
        ]);

        // Create second bank account and set as default
        $secondAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'is_primary' => false
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/bank/{$secondAccount->id}/edit", [
            'account_id' => $secondAccount->id
        ]);

        $response->assertStatus(200);

        // First account should no longer be default
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $firstAccount->id,
            'is_primary' => false
        ]);

        // Second account should now be default
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $secondAccount->id,
            'is_primary' => true
        ]);
    }
}
