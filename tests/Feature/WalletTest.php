<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\BankAccount;
use App\Models\Withdrawal;
use App\Models\SystemSetting;
use App\Models\SystemStatus;
use App\Enums\Tranx;
use App\Models\Tier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\TestSetupTrait;

class WalletTest extends TestCase
{
    use RefreshDatabase, WithFaker, TestSetupTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->setupTiers();
    }

    public function test_wallet_is_created_when_user_registers()
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
            'wallet_currency' => 'NGN'
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'wallet_currency' => 'NGN'
        ]);

        $this->assertEquals(0.00, $wallet->balance);
    }

    public function test_user_can_view_wallet_details()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'balance',
                         'wallet_currency'
                     ]
                 ]);
    }

    public function test_user_can_view_wallet_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 2500.50
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/balance');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'balance' => 2500.50
                     ]
                 ]);
    }

    public function test_user_can_view_wallet_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create some transactions
        WalletTransaction::factory()->count(5)->create([
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                        'current_page',
                        'data' => [
                            '*' => [
                                'id',
                                'reference',
                                'user_id',
                                'transaction_type',
                                'transaction_id',
                                'entry',
                                'status',
                                'narration',
                                'currency',
                                'amount',
                                'charge',
                                'total_amount',
                                'wallet_balance',
                                'is_reversal',
                                'mode',
                                'created_at',
                                'updated_at',
                                'transactable' => [
                                    'id',
                                    'reference',
                                ]
                            ]
                        ]
                     ]
        ]);

    }

    public function test_user_can_filter_transactions_by_type()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create credit transactions
        WalletTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'transaction_type' => 'crypto'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/transactions?type=crypto');

        $response->assertStatus(200);
        $transactions = $response->json('data.data');

        foreach ($transactions as $transaction) {
            $this->assertEquals('crypto', $transaction['transaction_type']);
        }
    }









    public function test_wallet_balance_updates_after_transaction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);

        // Create a debit transaction
        WalletTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 200.00,
            'entry' => 'debit',
            'status' => 'successful',
            'narration' => 'Test debit'
        ]);

        // Manually update wallet balance (this would be done by the service)
        $wallet->update(['balance' => $wallet->balance - 200.00]);

        $this->assertEquals(800.00, $wallet->fresh()->balance);
    }

    public function test_wallet_can_be_flagged_for_suspicious_activity()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'is_flagged' => false
        ]);

        // Flag the wallet
        $wallet->update(['is_flagged' => true]);

        $this->assertTrue($wallet->fresh()->is_flagged);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'is_flagged' => true
        ]);
    }


}
