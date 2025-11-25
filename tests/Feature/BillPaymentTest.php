<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\AirtimeTopUp;
use App\Models\DataTopUp;
use App\Models\CableTopUp;
use App\Models\MeterTopUp;
use App\Models\BettingTopUp;
use App\Models\WifiTopUp;
use App\Models\AirtimeProduct;
use App\Models\MeterProduct;
use App\Models\CableProvider;
use App\Models\BettingProduct;
use App\Models\WifiProvider;
use App\Models\IspProvider;
use App\Services\Payment\RedbillerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Mockery;

class BillPaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedProducts();

        // Mock RedbillerService
        $this->mockRedbillerService();
    }

    private function mockRedbillerService()
    {
        $mock = Mockery::mock(RedbillerService::class);

        // Mock successful purchase responses
        $successfulResponse = (object) [
            'response' => 200,
            'meta' => (object) ['status' => 'Approved'],
            'details' => (object) [
                'amount_paid' => 100,
                'discount_percentage' => 0,
                'discount_value' => 0,
                'token' => '1234567890123456789012345678901234567890'
            ]
        ];

        // Mock all purchase methods
        $mock->shouldReceive('purchaseAirtime')->andReturn($successfulResponse);
        $mock->shouldReceive('purchaseData')->andReturn($successfulResponse);
        $mock->shouldReceive('purchaseCablePlan')->andReturn($successfulResponse);
        $mock->shouldReceive('purchaseDisco')->andReturn($successfulResponse);
        $mock->shouldReceive('fundBetAccount')->andReturn($successfulResponse);
        $mock->shouldReceive('purchaseWiFi')->andReturn($successfulResponse);

        // Mock verification methods
        $mock->shouldReceive('verifyAirtimePurchase')->andReturn($successfulResponse);
        $mock->shouldReceive('verifyDataPurchase')->andReturn($successfulResponse);
        $mock->shouldReceive('verifyCablePlanPurchase')->andReturn($successfulResponse);
        $mock->shouldReceive('verifyDiscoPurchase')->andReturn($successfulResponse);
        $mock->shouldReceive('verifyBettingAccountCredit')->andReturn($successfulResponse);
        $mock->shouldReceive('verifyWifiPurchase')->andReturn($successfulResponse);

        // Mock other methods that might be called
        $mock->shouldReceive('setup3D')->andReturn(null);

        // Mock plan listing methods with actual plan data
        $wifiPlansResponse = (object) [
            'response' => 200,
            'data' => [
                (object) [
                    'code' => '1',
                    'name' => 'Test WiFi Plan',
                    'amount' => 1500,
                    'validity' => '30 days'
                ]
            ]
        ];

        $dataPlansResponse = (object) [
            'response' => 200,
            'details' => (object) [
                'categories' => [
                    (object) [
                        'code' => '1',
                        'name' => 'Test Data Plan',
                        'amount' => 350,
                        'validity' => '30 days'
                    ]
                ]
            ]
        ];

        $cablePlansResponse = (object) [
            'response' => 200,
            'details' => (object) [
                'categories' => [
                    (object) [
                        'code' => '1',
                        'name' => 'Test Cable Plan',
                        'amount' => 2000,
                        'validity' => '30 days'
                    ]
                ]
            ]
        ];

        $wifiPlansResponse = (object) [
            'response' => 200,
            'details' => (object) [
                'categories' => [
                    (object) [
                        'code' => '1',
                        'name' => 'Test WiFi Plan',
                        'amount' => 1500,
                        'validity' => '30 days'
                    ]
                ]
            ]
        ];

        $mock->shouldReceive('wifiPlans')->andReturn($wifiPlansResponse);
        $mock->shouldReceive('dataPlans')->andReturn($dataPlansResponse);
        $mock->shouldReceive('cablePlans')->andReturn($cablePlansResponse);
        $mock->shouldReceive('bettingProviders')->andReturn((object) ['response' => 200, 'data' => []]);

        // Mock verification methods for devices/accounts
        $verificationResponse = (object) [
            'response' => 200,
            'data' => (object) ['customer_name' => 'John Doe']
        ];
        $mock->shouldReceive('verifyDeviceNumber')->andReturn($verificationResponse);
        $mock->shouldReceive('verifySmartCardNumber')->andReturn($verificationResponse);
        $mock->shouldReceive('verifyMeterNumber')->andReturn($verificationResponse);
        $mock->shouldReceive('verifyBettingAccount')->andReturn($verificationResponse);

        $this->app->instance(RedbillerService::class, $mock);

        // Mock MeterBillService
        $meterMock = Mockery::mock(\App\Services\Bills\MeterBillService::class);
        $meterMock->shouldReceive('handle')->andReturn($successfulResponse);
        $this->app->instance(\App\Services\Bills\MeterBillService::class, $meterMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function seedProducts()
    {
        // Airtime products
        AirtimeProduct::create([
            'product' => 'MTN',
            'code' => 'MTN_AIRTIME',
            'logo' => 'mtn-logo.png',
            'status' => 'active',
            'minimum_amount' => 100,
            'maximum_amount' => 50000
        ]);

        AirtimeProduct::create([
            'product' => 'Airtel',
            'code' => 'AIRTEL_AIRTIME',
            'logo' => 'airtel-logo.png',
            'status' => 'active',
            'minimum_amount' => 100,
            'maximum_amount' => 50000
        ]);

        // Cable providers
        CableProvider::create([
            'product' => 'DSTV',
            'name' => 'DSTV',
            'logo' => 'dstv-logo.png',
            'status' => 'active'
        ]);

        CableProvider::create([
            'product' => 'GOTV',
            'name' => 'GOTV',
            'logo' => 'gotv-logo.png',
            'status' => 'active'
        ]);

        // Meter products
        MeterProduct::create([
            'name' => 'EKEDC Prepaid',
            'logo' => 'ekedc-logo.png'
        ]);

        // Betting products
        BettingProduct::create([
            'product' => 'Bet9ja',
            'logo' => 'bet9ja-logo.png',
            'minimum_amount' => 100.00,
            'maximum_amount' => 100000.00,
            'status' => 'active'
        ]);

        // WiFi providers
        WifiProvider::create([
            'product' => 'SPECTRANET',
            'name' => 'Spectranet',
            'logo' => 'spectranet-logo.png',
            'status' => 'active'
        ]);

        WifiProvider::create([
            'product' => 'SMILE',
            'name' => 'Smile',
            'logo' => 'smile-logo.png',
            'status' => 'active'
        ]);
    }

    private function createUserWithWallet($balance = 10000)
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance,
            'wallet_currency' => 'NGN'
        ]);
        return $user;
    }

    // Airtime Tests
    public function test_user_can_view_airtime_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/phonebill/isp');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'name',
                             'logo',
                             'status'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_purchase_airtime()
    {
        $user = $this->createUserWithWallet();
        $product = AirtimeProduct::where('product', 'MTN')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/phonebill/purchase-airtime', [
            'phone_no' => '08012345678',
            'network' => 'MTN',
            'amount' => 1000
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'phone_no',
                             'product',
                             'amount_requested',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('airtime_top_ups', [
            'user_id' => $user->id,
            'phone_no' => '08012345678',
            'product' => 'MTN',
            'amount_requested' => 1000
        ]);
    }

    public function test_airtime_purchase_fails_with_insufficient_balance()
    {
        $user = $this->createUserWithWallet(50); // Insufficient balance
        $product = AirtimeProduct::where('product', 'MTN')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/phonebill/purchase-airtime', [
            'phone_no' => '08012345678',
            'network' => 'MTN',
            'amount' => 1000
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Whoops something went wrong, Your available balance is insufficient to complete this transaction.'
                 ]);
    }

    // Data Tests
    public function test_user_can_view_data_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/phonebill/isp');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'name',
                             'logo',
                             'status'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_purchase_data()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/phonebill/purchase-data', [
            'phone_no' => '08012345678',
            'network' => 'MTN',
            'code' => '1'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'phone_no',
                             'product',
                             'name',
                             'code',
                             'amount_requested',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('data_top_ups', [
            'user_id' => $user->id,
            'phone_no' => '08012345678',
            'product' => 'MTN'
        ]);
    }

    // Cable Tests
    public function test_user_can_view_cable_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/cable/providers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'name',
                             'logo',
                             'status'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_purchase_cable_subscription()
    {
        $user = $this->createUserWithWallet(15000);
        $provider = CableProvider::create([
            'product' => 'DStv',
            'name' => 'DStv',
            'logo' => 'https://example.com/logo.png',
            'status' => 'active'
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cable/purchase', [
            'smart_card_no' => '1234567890',
            'product' => $provider->product,
            'code' => '1',
            'customer_name' => 'John Doe'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'smart_card_no',
                             'product',
                             'name',
                             'code',
                             'amount_requested',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('cable_top_ups', [
            'user_id' => $user->id,
            'smart_card_no' => '1234567890',
            'product' => $provider->product
        ]);
    }

    // Meter Tests
    public function test_user_can_view_meter_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meter/providers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'logo'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_purchase_meter_token()
    {
        $user = $this->createUserWithWallet();
        $product = MeterProduct::where('name', 'EKEDC Prepaid')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/meter/purchase', [
            'meter_no' => '12345678901',
            'product' => 'Abuja',
            'meter_type' => 'PREPAID',
            'customer_name' => 'John Doe',
            'amount' => 2000
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'meter_no',
                             'product',
                             'meter_type',
                             'amount_requested',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('meter_top_ups', [
            'user_id' => $user->id,
            'meter_no' => '12345678901',
            'product' => 'Abuja',
            'meter_type' => 'PREPAID',
            'amount_requested' => 2000
        ]);
    }

    // Betting Tests
    public function test_user_can_view_betting_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/betting/providers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'logo',
                             'minimum_amount',
                             'maximum_amount',
                             'status'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_fund_betting_account()
    {
        $user = $this->createUserWithWallet();
        $product = BettingProduct::where('product', 'Bet9ja')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/betting/fund', [
            'customer_id' => '123456789',
            'product' => 'Bet9ja',
            'amount' => '1000',
            "phone_number" => '08132554343'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'customer_id',
                             'product',
                             'amount',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('betting_top_ups', [
            'user_id' => $user->id,
            'customer_id' => '123456789',
            'product' => 'Bet9ja',
            'amount' => '1000'
        ]);
    }

    // WiFi Tests
    public function test_user_can_view_wifi_products()
    {
        $user = $this->createUserWithWallet();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wifi/providers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'name',
                             'logo',
                             'status'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_purchase_wifi_subscription()
    {
        $user = $this->createUserWithWallet();
        $provider = WifiProvider::where('product', 'SPECTRANET')->first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wifi/purchase', [
            'device_no' => 123456,
            'product' => 'Smile',
            'code' => '1',
            'customer_name' => 'John Doe'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'reference',
                         'transaction_type',
                         'amount',
                         'status',
                         'transactable' => [
                             'id',
                             'device_number',
                             'product',
                             'name',
                             'code',
                             'amount_requested',
                             'status',
                             'reference'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('wifi_top_ups', [
            'user_id' => $user->id,
            'device_number' => '123456',
            'product' => 'Smile'
        ]);
    }

    // Validation Tests
    public function test_airtime_purchase_requires_valid_phone_number()
    {
        $user = $this->createUserWithWallet();
        $product = AirtimeProduct::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/phonebill/purchase-airtime', [
            'phone_no' => 'invalid_phone',
            'network' => 'MTN',
            'amount' => 100
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['phone_no']);
    }

    public function test_meter_purchase_validates_amount_range()
    {
        $user = $this->createUserWithWallet();
        $product = MeterProduct::first();

        Sanctum::actingAs($user);

        // Test amount below minimum
        $response = $this->postJson('/api/meter/purchase', [
            'meter_no' => '12345678901',
            'product' => 'Abuja',
            'meter_type' => 'PREPAID',
            'customer_name' => 'John Doe',
            'amount' => 400 // Below minimum of 500
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);

        // Test amount above maximum
        $response = $this->postJson('/api/meter/purchase', [
            'meter_no' => '12345678901',
            'product' => 'Abuja',
            'meter_type' => 'PREPAID',
            'customer_name' => 'John Doe',
            'amount' => 60000 // Above maximum
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['amount']);
    }
}
