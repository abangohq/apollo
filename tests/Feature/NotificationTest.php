<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PushNotification;
use App\Models\SystemSetting;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedNotificationData();
    }

    private function seedNotificationData()
    {
        // Create notification settings
        SystemSetting::create([
            'key' => 'push_notifications_enabled',
            'value' => 'true',
            'description' => 'Enable or disable push notifications'
        ]);

        SystemSetting::create([
            'key' => 'email_notifications_enabled',
            'value' => 'true',
            'description' => 'Enable or disable email notifications'
        ]);
    }

    private function createUserWithWallet($balance = 10000)
    {
        $user = User::factory()->create([
            'device_token' => 'test_device_token_' . time()
        ]);
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance
        ]);
        return $user;
    }

    private function createAdminUser()
    {
        return User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin'
        ]);
    }

    // Push Notification Management Tests
    public function test_admin_can_create_push_notification()
    {
        $admin = $this->createAdminUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/notification/bulletin', [
            'title' => 'System Update',
            'body' => 'We have updated our system with new features',
            'target' => 'all_users'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'title',
                         'body',
                         'group',
                         'successful',
                         'failed'
                     ]
                 ]);

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'System Update',
            'group' => 'All Users'
        ]);
    }

    public function test_admin_can_create_targeted_push_notification()
    {
        $admin = $this->createAdminUser();
        $targetUser = $this->createUserWithWallet();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/notification/bulletin', [
            'title' => 'Personal Message',
            'body' => 'This is a personal message for you',
            'target' => 'specific_user',
            'user' => $targetUser->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'Personal Message',
            'group' => 'Specific User'
        ]);
    }

    public function test_admin_can_view_push_notifications()
    {
        $admin = $this->createAdminUser();

        // Create some notifications
        PushNotification::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/console/notification');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id',
                                 'title',
                                 'body',
                                 'group',
                                 'successful',
                                 'failed',
                                 'created_at'
                             ]
                         ]
                     ]
                 ]);
    }

    public function test_notification_creation_requires_valid_data()
    {
        $admin = $this->createAdminUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/console/notification/bulletin', [
            'title' => '', // Empty title
            'body' => 'Test message',
            'target' => 'invalid_type' // Invalid target type
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title', 'target']);
    }
}
