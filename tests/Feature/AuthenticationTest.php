<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    public function test_user_can_register_successfully()
    {
        $userData = [
            'name' => $this->faker->name,
            'username' => 'testuser_' . $this->faker->unique()->randomNumber(4),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register/step/step-1', $userData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'name',
                         'username',
                         'email'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'Email verification token sent successfully'
                 ]);

        // Step 1 only sends verification token, no user is created yet
        $this->assertDatabaseMissing('users', [
            'email' => $userData['email']
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user',
                         'token'
                     ]
                 ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Whoops something went wrong, Wrong login credentials. Enter a valid credentials.'
                 ]);
    }

    public function test_user_can_login_with_pin()
    {
        $user = User::factory()->create([
            'pin' => Hash::make('1234')
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/login/pin', [
            'pin' => '1234'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }

    public function test_user_can_set_biometric_authentication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/biometric', [
            'type' => 'face',
            'hash' => 'fc5731c66ab13b9cfeb8978fbffe6b83192ac559',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'face_id' => 'fc5731c66ab13b9cfeb8978fbffe6b83192ac559'
        ]);
    }

    public function test_user_can_logout_successfully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'user, logged out successfully.'
                 ]);
    }

    public function test_user_login_attempts_are_tracked()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'failed_login_attempts' => 0
        ]);

        // Make failed login attempts
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        $user->refresh();
        $this->assertEquals(3, $user->failed_login_attempts);
    }

    public function test_user_account_gets_locked_after_max_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'failed_login_attempts' => User::MAX_LOGIN_ATTEMPT
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'you have exceeded the allowed login attempts, please reset your password'
                 ]);
    }

    public function test_password_reset_request_works()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $response = $this->postJson('/api/password/reset-request', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     "success" => true,
                     "message" => "One time password for otp reset has been sent."
                 ]);

        $this->assertDatabaseHas('verify_tokens', [
            'email' => 'test@example.com'
        ]);
    }
}
