<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Kyc;
use App\Models\Tier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seedTiers();

    }

    private function seedTiers()
    {
        Tier::create([
            'name' => 'Basic',
            'level' => 1,
            'title' => "24h Withdrawal Limit",
            'withdrawal_limit' => 200000,
            'requirements' => json_encode(['phone_verification'])
        ]);

        Tier::create([
            'name' => 'Standard',
            'level' => 2,
            'title' => 200000,
            'withdrawal_limit' => 1000000,
            'requirements' => json_encode(['bvn_verification'])
        ]);

        Tier::create([
            'name' => 'Premium',
            'level' => 3,
            'title' => "24h Withdrawal Limit",
            'withdrawal_limit' => 5000000,
            'requirements' => json_encode(['bvn_verification'])
        ]);
    }

    public function test_user_can_view_kyc_tiers()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tiers');
        // dd($response);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'title',
                             'withdrawal_limit',
                             'requirements',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);

        $tiers = $response->json('data');
        $this->assertCount(3, $tiers);
        
        // Check that first tier has status (added by repository)
        $this->assertArrayHasKey('status', $tiers[0]);
        $this->assertEquals('completed', $tiers[0]['status']);
        
        // Check that second tier has status (added by repository)
        $this->assertArrayHasKey('status', $tiers[1]);
        $this->assertContains($tiers[1]['status'], ['idle', 'pending', 'successful', 'rejected']);
        
        // Verify tier data matches seeded data
        $this->assertEquals('Basic', $tiers[0]['name']);
        $this->assertEquals('200000', $tiers[0]['withdrawal_limit']);
        $this->assertIsArray(json_decode($tiers[0]['requirements'], true));
    }
















}
