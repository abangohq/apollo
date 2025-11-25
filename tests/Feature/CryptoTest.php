<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CryptoAsset;
use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Models\SwapTransaction;
use App\Models\CryptoRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedCryptoAssets();
    }

    private function seedCryptoAssets()
    {
        CryptoAsset::create([
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'logo' => 'btc-logo.png',
            'is_active' => true
        ]);

        CryptoAsset::create([
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'logo' => 'eth-logo.png',
            'is_active' => true
        ]);

        CryptoAsset::create([
            'name' => 'Tether',
            'symbol' => 'USDT',
            'logo' => 'usdt-logo.png',
            'is_active' => true
        ]);
    }

    public function test_user_can_view_available_crypto_assets()
    {
        $response = $this->getJson('/api/crypto/assets');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                            'id',
                            'name',
                            'symbol',
                            'logo',
                        ]
                     ]
                 ]);

        $assets = $response->json('data');
        $this->assertCount(3, $assets);
    }

    public function test_user_can_view_specific_crypto_asset()
    {
        $response = $this->getJson('/api/crypto/assets/BTC');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'symbol',
                         'logo',
                     ]
                 ]);

        $asset = $response->json('data');
        $this->assertEquals('BTC', $asset['symbol']);
        $this->assertEquals('Bitcoin', $asset['name']);
    }

    public function test_user_can_view_crypto_rates()
    {
        // Create some crypto rates
        CryptoRate::create([
            'range_start' => '1000',
            'range_end' => '10000',
            'rate' => 45000.00,
            'rate_range' => 'Below $50k'
        ]);

        CryptoRate::create([
            'range_start' => '100',
            'range_end' => '1000',
            'rate' => 3000.00,
            'rate_range' => 'Below $10k'
        ]);

        $response = $this->getJson('/api/crypto/rates');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                        [
                         'id',
                         'rate',
                         'rate_range',
                         'range_start',
                         'range_end'
                        ]
                     ]
                 ]);
    }

    public function test_authenticated_user_can_create_crypto_wallet()
    {
        $user = User::factory()->create();
        $cryptoAsset = CryptoAsset::where('symbol', 'BTC')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/crypto/wallets/create', [
            'symbol' => $cryptoAsset->symbol
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'chain',
                         'address',
                         'crypto_asset_id',
                         'user_id'
                     ]
                 ]);

        $this->assertDatabaseHas('crypto_wallets', [
            'user_id' => $user->id,
            'crypto_asset_id' => $cryptoAsset->id
        ]);
    }

    public function test_user_can_view_specific_crypto_wallet()
    {
        $user = User::factory()->create();
        $cryptoAsset = CryptoAsset::where('symbol', 'BTC')->first();

        $wallet = CryptoWallet::factory()->create([
            'user_id' => $user->id,
            'crypto_asset_id' => $cryptoAsset->id,
            'address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'balance' => 0.5
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/crypto/wallets/BTC');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'address',
                         'balance',
                     ]
                 ]);
    }

    // public function test_user_can_get_swap_rates()
    // {
    //     $user = User::factory()->create();
    //     Sanctum::actingAs($user);

    //     $response = $this->postJson('/api/crypto/swap/rates', [
    //         'from_currency' => 'BTC',
    //         'to_currency' => 'ETH',
    //         'amount' => 0.1
    //     ]);

    //     $response->assertStatus(200)
    //              ->assertJsonStructure([
    //                  'success',
    //                  'message',
    //                  'data' => [
    //                      'from_currency',
    //                      'to_currency',
    //                      'from_amount',
    //                      'to_amount',
    //                      'exchange_rate'
    //                  ]
    //              ]);
    // }

    // public function test_user_can_view_swap_currencies()
    // {
    //     $user = User::factory()->create();
    //     Sanctum::actingAs($user);

    //     $response = $this->getJson('/api/crypto/swap/currencies');

    //     $response->assertStatus(200)
    //              ->assertJsonStructure([
    //                  'success',
    //                  'message',
    //                  'data' => [
    //                      'currencies' => [
    //                          '*' => [
    //                              'symbol',
    //                              'name',
    //                              'logo'
    //                          ]
    //                      ]
    //                  ]
    //              ]);
    // }

    // public function test_user_can_create_swap_transaction()
    // {
    //     $user = User::factory()->create();

    //     // Create crypto wallets for the user
    //     $btcWallet = CryptoWallet::factory()->create([
    //         'user_id' => $user->id,
    //         'crypto_asset_id' => CryptoAsset::where('symbol', 'BTC')->first()->id,
    //         'balance' => 1.0
    //     ]);

    //     Sanctum::actingAs($user);

    //     $response = $this->postJson('/api/crypto/swap/create', [
    //         'from_currency' => 'BTC',
    //         'to_currency' => 'ETH',
    //         'from_amount' => 0.1,
    //         'to_amount' => 3.5,
    //         'refund_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'
    //     ]);

    //     $response->assertStatus(201)
    //              ->assertJsonStructure([
    //                  'success',
    //                  'message',
    //                  'data' => [
    //                      'swap' => [
    //                          'id',
    //                          'from_currency',
    //                          'to_currency',
    //                          'from_amount',
    //                          'to_amount',
    //                          'success'
    //                      ]
    //                  ]
    //              ]);

    //     $this->assertDatabaseHas('swap_transactions', [
    //         'user_id' => $user->id,
    //         'from_currency' => 'BTC',
    //         'to_currency' => 'ETH',
    //         'from_amount' => 0.1
    //     ]);
    // }

    // public function test_user_can_view_swap_transaction_details()
    // {
    //     $user = User::factory()->create();

    //     $swapTransaction = SwapTransaction::factory()->create([
    //         'user_id' => $user->id,
    //         'from_currency' => 'BTC',
    //         'to_currency' => 'ETH',
    //         'from_amount' => 0.1,
    //         'to_amount' => 3.5,
    //         'status' => 'pending'
    //     ]);

    //     Sanctum::actingAs($user);

    //     $response = $this->getJson("/api/crypto/swap/{$swapTransaction->id}/details");

    //     $response->assertStatus(200)
    //              ->assertJsonStructure([
    //                  'success',
    //                  'message',
    //                  'data' => [
    //                      'swap' => [
    //                          'id',
    //                          'from_currency',
    //                          'to_currency',
    //                          'from_amount',
    //                          'to_amount',
    //                          'success',
    //                          'created_at'
    //                      ]
    //                  ]
    //              ]);
    // }

    // public function test_user_total_crypto_trade_is_calculated_correctly()
    // {
    //     $user = User::factory()->create();

    //     // Create successful crypto transactions
    //     CryptoTransaction::factory()->count(3)->create([
    //         'user_id' => $user->id,
    //         'usd_value' => 1000.00,
    //         'status' => 'successful'
    //     ]);

    //     // Create a pending transaction (should not be counted)
    //     CryptoTransaction::factory()->create([
    //         'user_id' => $user->id,
    //         'usd_value' => 500.00,
    //         'status' => 'pending'
    //     ]);

    //     $totalTrade = $user->getTotalCryptoTradeAttribute();

    //     $this->assertEquals(3000.00, $totalTrade);
    // }

    public function test_user_cannot_create_duplicate_crypto_wallet()
    {
        $user = User::factory()->create();
        $cryptoAsset = CryptoAsset::where('symbol', 'BTC')->first();

        // Create first wallet
        CryptoWallet::factory()->create([
            'user_id' => $user->id,
            'crypto_asset_id' => $cryptoAsset->id,
            'chain' => 'BTC'
        ]);

        Sanctum::actingAs($user);

        // Try to create another wallet for the same asset
        $response = $this->postJson('/api/crypto/wallets/create', [
            'symbol' => 'BTC'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                    "success" => false,
                    "message" => "Whoops something went wrong, You have a wallet already.",
                    "errors" => [
                        "symbol" => [
                            "You have a wallet already."
                        ]
                    ]
                ]);
    }
}
