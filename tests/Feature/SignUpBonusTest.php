<?php

namespace Tests\Feature;

use App\Models\SignUpBonus;
use App\Models\User;
use App\Services\SignUpBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpBonusTest extends TestCase
{
    use RefreshDatabase;

    protected SignUpBonusService $signUpBonusService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signUpBonusService = new SignUpBonusService();
    }

    /** @test */
    public function it_creates_sign_up_bonus_for_new_user()
    {
        $user = User::factory()->create();

        $signUpBonus = $this->signUpBonusService->createSignUpBonus($user, 500);

        $this->assertInstanceOf(SignUpBonus::class, $signUpBonus);
        $this->assertEquals($user->id, $signUpBonus->user_id);
        $this->assertEquals(500.00, $signUpBonus->bonus_amount);
        $this->assertEquals(200.00, $signUpBonus->required_trade_volume);
        $this->assertEquals(0.00, $signUpBonus->current_trade_volume);
        $this->assertEquals(SignUpBonus::STATUS_PENDING, $signUpBonus->status);
    }

    /** @test */
    public function it_updates_trade_volume_and_unlocks_bonus()
    {
        $user = User::factory()->create();
        $signUpBonus = $this->signUpBonusService->createSignUpBonus($user, 500);

        // Mock the user's total crypto trade to be above the required amount
        $user->shouldReceive('getTotalCryptoTradeAttribute')->andReturn(250.00);

        $this->signUpBonusService->updateTradeVolume($user);

        $signUpBonus->refresh();
        $this->assertEquals(250.00, $signUpBonus->current_trade_volume);
        $this->assertEquals(SignUpBonus::STATUS_UNLOCKED, $signUpBonus->status);
        $this->assertNotNull($signUpBonus->unlocked_at);
    }

    /** @test */
    public function it_returns_correct_bonus_status()
    {
        $user = User::factory()->create();
        $signUpBonus = $this->signUpBonusService->createSignUpBonus($user, 500);

        $status = $this->signUpBonusService->getBonusStatus($user);

        $this->assertIsArray($status);
        $this->assertEquals(SignUpBonus::STATUS_PENDING, $status['status']);
        $this->assertEquals(500.00, $status['bonus_amount']);
        $this->assertEquals(200.00, $status['required_trade_volume']);
        $this->assertEquals(0.00, $status['current_trade_volume']);
        $this->assertEquals(0, $status['progress_percentage']);
        $this->assertEquals(200.00, $status['remaining_trade_volume']);
        $this->assertFalse($status['can_claim']);
    }

    /** @test */
    public function it_prevents_claiming_pending_bonus()
    {
        $user = User::factory()->create();
        $this->signUpBonusService->createSignUpBonus($user, 500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sign-up bonus is not available for claiming');

        $this->signUpBonusService->claimBonus($user);
    }

    /** @test */
    public function it_returns_null_for_user_without_bonus()
    {
        $user = User::factory()->create();

        $status = $this->signUpBonusService->getBonusStatus($user);

        $this->assertNull($status);
    }

    /** @test */
    public function api_returns_bonus_status()
    {
        $user = User::factory()->create();
        $this->signUpBonusService->createSignUpBonus($user, 500);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/signup-bonus/status');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'has_bonus',
                    'bonus' => [
                        'status',
                        'bonus_amount',
                        'required_trade_volume',
                        'current_trade_volume',
                        'progress_percentage',
                        'remaining_trade_volume',
                        'can_claim'
                    ]
                ]
            ]);
    }

    /** @test */
    public function api_returns_no_bonus_for_user_without_bonus()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/signup-bonus/status');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'has_bonus' => false,
                    'message' => 'No sign-up bonus available'
                ]
            ]);
    }
}
