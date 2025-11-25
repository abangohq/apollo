<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Models\SystemSetting;
use App\Models\AppVersion;
use App\Models\PushNotification;
use App\Models\RejectionReason;
use App\Models\AirtimeProduct;
use App\Models\BettingProduct;
use App\Models\MeterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedAdminData();
    }

    private function seedAdminData()
    {
        // Create admin user
        $admin = User::factory()->create([
            'email' => 'admin@getkoyn.com',
            'user_type' => 'admin',
            'role' => 'admin'
        ]);

        // Create system settings
        SystemSetting::create([
            'key' => 'withdrawal_limit_daily',
            'value' => '100000',
            'description' => 'Daily withdrawal limit'
        ]);

        SystemSetting::create([
            'key' => 'platform_status',
            'value' => 'active',
            'description' => 'Platform operational status'
        ]);

        // Create app version
        AppVersion::create([
            'version_number' => '1.0.0',
            'release_date' => '100',
            'message' => 'New features and bug fixes'
        ]);

        // Create rejection reasons
        RejectionReason::create([
            'reason' => 'Insufficient documentation',
            'type' => 'kyc',
            'is_active' => true
        ]);

        RejectionReason::create([
            'reason' => 'Invalid account details',
            'type' => 'withdrawal',
            'is_active' => true
        ]);
    }

    private function createAdminUser()
    {
        return User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin'
        ]);
    }

    private function createRegularUser()
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 25000
        ]);
        return $user;
    }

    // Admin Authentication Tests
    public function test_admin_can_login_to_console()
    {
        $admin = $this->createAdminUser();

        $response = $this->postJson('/api/console/login', [
            'email' => $admin->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'user_type',
                             'role'
                         ],
                         'token'
                     ]
                 ]);
    }

    public function test_regular_user_cannot_login_to_console()
    {
        $user = $this->createRegularUser();

        $response = $this->postJson('/api/console/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Whoops something went wrong, No record found. Enter a valid credentials.',
                     'errors' => [
                         'email' => [
                             'No record found. Enter a valid credentials.'
                         ]
                     ]
                 ]);
    }

    // User Management Tests
    public function test_admin_can_view_all_users()
    {
        $admin = $this->createAdminUser();

        // Create some regular users
        User::factory()->count(5)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'email',
                                 'phone',
                                 'status',
                                 'created_at'
                             ]
                         ],
                         'first_page_url',
                         'last_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }

    public function test_admin_can_view_specific_user_details()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/console/users/{$user->id}/basic");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'phone',
                         'status',
                         'created_at',
                         'kycs',
                         'banks',
                         'cryptowallets'
                     ]
                 ]);
    }

    public function test_admin_can_suspend_user()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/users/{$user->id}/suspend", [
            'reason' => 'Suspicious activity detected'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User disabled successfully!'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_enable_suspended_user()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['status' => 'inactive']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/users/{$user->id}/unsuspend");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User enabled successfully!'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'active'
        ]);
    }

    // Withdrawal Management Tests
    public function test_admin_can_view_pending_withdrawals()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        // Create pending withdrawals
        Withdrawal::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/withdrawals?status=pending');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => [
                                 'id',
                                 'amount',
                                 'status',
                                 'reference',
                                 'user',
                                 'created_at'
                             ]
                         ],
                         'overview' => [
                             'successful',
                             'pending',
                             'rejected'
                         ],
                         'first_page_url',
                         'last_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }

    public function test_admin_can_approve_withdrawal()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
            'channel' => 'manual'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/withdrawals/approve/{$withdrawal->id}", [
            'admin_note' => 'Approved after verification'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Transaction has been queued and processing.'
                 ]);

        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id
        ]);
    }

    public function test_admin_can_decline_withdrawal()
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $rejectionReason = RejectionReason::create([
            'reason' => 'Invalid bank details',
            'type' => 'withdrawal'
        ]);

        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
            'channel' => 'manual'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/withdrawals/decline/{$withdrawal->id}", [
            'reason' => $rejectionReason->id,
            'admin_note' => 'Invalid bank account details'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Withdrawal request has been declined successfully!'
                 ]);

        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id
        ]);
    }

    // System Settings Tests
    public function test_admin_can_view_system_settings()
    {
        $admin = $this->createAdminUser();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/settings');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'key',
                             'value',
                             'created_at'
                         ]
                     ]
                 ]);
    }

    public function test_admin_can_update_system_setting()
    {
        $admin = $this->createAdminUser();
        $setting = \App\Models\SystemSetting::create([
            'key' => 'max_automatic_withdrawal_amount',
            'value' => '100000'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/settings/withdrawal-limit', [
            'limit' => '150000'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Limit Set Successfully.'
                 ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'max_automatic_withdrawal_amount',
            'value' => '150000'
        ]);
    }

    public function test_admin_can_update_platform_status()
    {
        $admin = $this->createAdminUser();

        // Create a system status record
        $systemStatus = \App\Models\SystemStatus::create([
            'key' => 'platform_status',
            'value' => true,
            'message' => 'Platform is operational'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/settings/platform/{$systemStatus->id}", [
            'value' => false,
            'message' => 'Platform under maintenance'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Status set Successfully.'
                 ]);

        $this->assertDatabaseHas('system_statuses', [
            'id' => $systemStatus->id,
            'value' => false
        ]);
    }

    // App Version Management Tests
    public function test_admin_can_update_app_version()
    {
        $admin = $this->createAdminUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/settings/app-version', [
            'version_number' => '1.1.0',
            'release_date' => now()->toDateString(),
            'message' => 'Critical security updates'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Version set successfully'
                 ]);

        $this->assertDatabaseHas('app_versions', [
            'version_number' => '1.1.0',
            'message' => 'Critical security updates'
        ]);
    }

    // Push Notification Tests
    public function test_admin_can_send_push_notification()
    {
        $admin = $this->createAdminUser();

        // Create some users to send notifications to
        User::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/notification/bulletin', [
            'title' => 'System Maintenance',
            'body' => 'Platform will be under maintenance from 2AM to 4AM',
            'target' => 'all_users'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'successful'
                 ]);

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'System Maintenance',
            'group' => 'All Users'
        ]);
    }

    // Product Management Tests
    public function test_admin_can_view_airtime_products()
    {
        $admin = $this->createAdminUser();

        // Create airtime products
        AirtimeProduct::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/airtime/products');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'product',
                             'code',
                             'logo',
                             'status',
                             'minimum_amount',
                             'maximum_amount',
                             'created_at'
                         ]
                     ]
                 ]);
    }

    public function test_admin_can_update_airtime_product()
    {
        $admin = $this->createAdminUser();
        $product = AirtimeProduct::factory()->create([
            'product' => 'MTN',
            'minimum_amount' => 100,
            'maximum_amount' => 1000
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/console/airtime/products/{$product->id}", [
            'product' => 'MTN',
            'minimum_amount' => 50,
            'status' => 'active'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Product updated successfully!'
                 ]);

        $this->assertDatabaseHas('airtime_products', [
            'id' => $product->id,
            'minimum_amount' => 50
        ]);
    }

    // Staff Management Tests
    public function test_admin_can_create_staff_account()
    {
        $admin = $this->createAdminUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/staffs/create', [
            'name' => 'John Staff',
            'email' => 'staff@pikka.com',
            'password' => 'password123',
            'role' => 'accountant'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'successful'
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'staff@pikka.com',
            'user_type' => 'staff'
        ]);
    }

    public function test_admin_can_delete_staff_account()
    {
        $admin = $this->createAdminUser();
        $staff = User::factory()->create([
            'user_type' => 'staff'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/console/staffs/{$staff->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User deleted successfully'
                 ]);

        $this->assertSoftDeleted('users', [
            'id' => $staff->id
        ]);
    }

    // Analytics Tests
    public function test_admin_can_view_dashboard_analytics()
    {
        $admin = $this->createAdminUser();

        // Create some data for analytics
        User::factory()->count(10)->create();
        WalletTransaction::factory()->count(20)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/overview');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'all_time' => [
                             'users',
                             'crypto',
                             'withdrawals'
                         ],
                         'month' => [
                             'users',
                             'crypto',
                             'withdrawals'
                         ],
                         'today' => [
                             'users',
                             'crypto',
                             'withdrawals'
                         ],
                         'wallet_balance',
                         'redbiller_balance'
                     ]
                 ]);
    }

    // Access Control Tests
    public function test_regular_user_cannot_access_admin_endpoints()
    {
        $user = $this->createRegularUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/console/users');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'This action is unauthorized.'
                 ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints()
    {
        $response = $this->getJson('/api/console/users');

        $response->assertStatus(401);
    }
}
