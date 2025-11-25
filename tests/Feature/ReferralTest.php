<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Referral;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedReferralData();
    }

    private function seedReferralData()
    {
        // Create referral system settings
        SystemSetting::create([
            'key' => 'referral_bonus_amount',
            'value' => '500',
            'description' => 'Referral bonus amount in naira'
        ]);

        SystemSetting::create([
            'key' => 'referral_minimum_transaction',
            'value' => '1000',
            'description' => 'Minimum transaction amount to qualify for referral bonus'
        ]);

        SystemSetting::create([
            'key' => 'referral_system_enabled',
            'value' => 'true',
            'description' => 'Enable or disable referral system'
        ]);
    }

    private function createUserWithWallet($balance = 10000)
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => $balance
        ]);
        return $user;
    }

    // Referral Code Tests
    public function test_user_gets_referral_code_on_registration()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08012345678'
        ]);

        $this->assertNotNull($user->referral_code);
        $this->assertEquals(8, strlen($user->referral_code));
    }

    public function test_referral_code_is_unique()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertNotEquals($user1->referral_code, $user2->referral_code);
    }

    public function test_user_has_referral_amount_fields()
    {
        $user = $this->createUserWithWallet();

        $this->assertNotNull($user->referral_code);
        $this->assertIsInt($user->referral_amount_available);
        $this->assertIsInt($user->referral_amount_redeemed);
    }

    // Referral Model Tests
    public function test_can_create_referral_record()
    {
        $user = $this->createUserWithWallet();

        $referral = Referral::create([
            'user_id' => $user->id,
            'code' => 'TEST1234',
            'reward_amount' => 500
        ]);

        $this->assertDatabaseHas('referrals', [
            'user_id' => $user->id,
            'code' => 'TEST1234',
            'reward_amount' => 500
        ]);

        $this->assertInstanceOf(Referral::class, $referral);
    }

    public function test_referral_belongs_to_user()
    {
        $user = $this->createUserWithWallet();

        $referral = Referral::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertEquals($user->id, $referral->user_id);
        $this->assertInstanceOf(User::class, $referral->user);
    }

    public function test_user_can_have_only_one_referral_due_to_unique_constraint()
    {
        $user = $this->createUserWithWallet();

        $referral1 = Referral::create([
            'user_id' => $user->id,
            'code' => 'REF001',
            'reward_amount' => 100
        ]);

        $this->assertDatabaseHas('referrals', [
            'user_id' => $user->id,
            'code' => 'REF001'
        ]);

        // Attempting to create another referral for the same user should fail
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Referral::create([
            'user_id' => $user->id,
            'code' => 'REF002',
            'reward_amount' => 200
        ]);
    }

    // Referral Reward Tests
    public function test_referral_reward_amount_can_be_updated()
    {
        $user = $this->createUserWithWallet();

        $referral = Referral::factory()->create([
            'user_id' => $user->id,
            'reward_amount' => 100
        ]);

        $referral->update(['reward_amount' => 500]);

        $this->assertEquals(500, $referral->fresh()->reward_amount);
    }

    public function test_referral_factory_creates_valid_records()
    {
        $referral = Referral::factory()->create();

        $this->assertDatabaseHas('referrals', [
            'user_id' => $referral->user_id,
            'code' => $referral->code,
            'reward_amount' => $referral->reward_amount
        ]);

        $this->assertIsString($referral->code);
        $this->assertIsNumeric($referral->reward_amount);
        $this->assertNotNull($referral->user_id);
    }

    public function test_system_settings_exist()
    {
        $bonusAmount = SystemSetting::where('key', 'referral_bonus_amount')->first();
        $minTransaction = SystemSetting::where('key', 'referral_minimum_transaction')->first();
        $systemEnabled = SystemSetting::where('key', 'referral_system_enabled')->first();

        $this->assertNotNull($bonusAmount);
        $this->assertEquals('500', $bonusAmount->value);

        $this->assertNotNull($minTransaction);
        $this->assertEquals('1000', $minTransaction->value);

        $this->assertNotNull($systemEnabled);
        $this->assertEquals('true', $systemEnabled->value);
    }

}
