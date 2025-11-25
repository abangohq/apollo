<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IntercomJwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntercomJwtTest extends TestCase
{
    use RefreshDatabase;

    private IntercomJwtService $jwtService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(IntercomJwtService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_generate_jwt_token()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/generate-token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'jwt_token',
                    'user_hash',
                    'user_id',
                    'email',
                    'name',
                    'platform',
                    'created_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.jwt_token'));
        $this->assertEquals('web', $response->json('data.platform'));
    }

    public function test_can_generate_jwt_token_with_platform()
    {
        Sanctum::actingAs($this->user);

        // Test Android platform
        $response = $this->postJson('/api/intercom/generate-token', [
            'platform' => 'android'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'jwt_token',
                    'user_hash',
                    'user_id',
                    'email',
                    'name',
                    'platform',
                    'created_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('android', $response->json('data.platform'));

        // Test iOS platform
        $response = $this->postJson('/api/intercom/generate-token', [
            'platform' => 'ios'
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('ios', $response->json('data.platform'));
    }

    public function test_can_verify_jwt_token()
    {
        $token = $this->jwtService->generateToken($this->user, 'android');
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/verify-token', [
            'token' => $token
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'email',
                    'name',
                    'platform',
                    'expires_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.user_id'));
        $this->assertEquals('android', $response->json('data.platform'));
    }

    public function test_can_get_user_from_token()
    {
        $token = $this->jwtService->generateToken($this->user);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/get-user', [
            'token' => $token
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.id'));
    }

    public function test_can_generate_user_hash()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/generate-user-hash');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_hash',
                    'user_id',
                    'platform'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.user_id'));
        $this->assertEquals('web', $response->json('data.platform'));
    }

    public function test_can_generate_user_hash_with_platform()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/generate-user-hash', [
            'platform' => 'android'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_hash',
                    'user_id',
                    'platform'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->user->id, $response->json('data.user_id'));
        $this->assertEquals('android', $response->json('data.platform'));
    }

    public function test_requires_authentication()
    {
        $response = $this->postJson('/api/intercom/generate-token');
        $response->assertStatus(401);
    }

    public function test_rejects_invalid_platform()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/intercom/generate-token', [
            'platform' => 'invalid_platform'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_jwt_service_can_generate_and_verify_token()
    {
        $token = $this->jwtService->generateToken($this->user, 'ios');
        $this->assertNotEmpty($token);

        $decoded = $this->jwtService->verifyToken($token);
        $this->assertEquals($this->user->id, $decoded->user_id);
        $this->assertEquals($this->user->email, $decoded->email);
        $this->assertEquals($this->user->name, $decoded->name);
        $this->assertEquals('ios', $decoded->platform);
    }

    public function test_jwt_service_can_get_user_from_token()
    {
        $token = $this->jwtService->generateToken($this->user);
        $retrievedUser = $this->jwtService->getUserFromToken($token);

        $this->assertNotNull($retrievedUser);
        $this->assertEquals($this->user->id, $retrievedUser->id);
    }

    public function test_invalid_token_returns_null_user()
    {
        $invalidToken = 'invalid.jwt.token';
        $user = $this->jwtService->getUserFromToken($invalidToken);

        $this->assertNull($user);
    }
}