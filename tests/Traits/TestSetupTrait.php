<?php

namespace Tests\Traits;

use App\Models\Bank;
use App\Models\SystemSetting;
use App\Models\SystemStatus;
use App\Models\Task;
use App\Models\Tier;

trait TestSetupTrait
{
    /**
     * Set up common system configurations for withdrawal tests
     */
    protected function setupWithdrawalEnvironment(): void
    {
        // Enable withdrawals in system
        SystemStatus::create([
            'id' => \Illuminate\Support\Str::orderedUuid(),
            'key' => 'withdrawal',
            'value' => true,
            'message' => 'Withdrawals are enabled'
        ]);

        // Create system settings for withdrawal
        SystemSetting::create([
            'id' => \Illuminate\Support\Str::orderedUuid(),
            'key' => 'withdrawal_mode',
            'value' => 'automatic'
        ]);

        SystemSetting::create([
            'id' => \Illuminate\Support\Str::orderedUuid(),
            'key' => 'max_automatic_withdrawal_amount',
            'value' => '1000000'
        ]);

        SystemSetting::create([
            'id' => \Illuminate\Support\Str::orderedUuid(),
            'key' => 'payment_gateway',
            'value' => 'redbiller'
        ]);
    }

    /**
     * Set up common banks for testing
     */
    protected function setupBanks(): void
    {
        Bank::insert([
            [
                'id' => 1,
                'bank_name' => 'Access Bank',
                'bank_code' => '044',
                'bank_logo' => 'access-bank.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'bank_name' => 'GTBank',
                'bank_code' => '058',
                'bank_logo' => 'gtbank.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'bank_name' => 'First Bank',
                'bank_code' => '011',
                'bank_logo' => 'first-bank.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Set up common system configurations
     */
    protected function setupSystemConfigurations(): void
    {
        SystemSetting::insert([
            [
                'id' => 1,
                'key' => 'app_name',
                'value' => 'KOYN',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'key' => 'app_version',
                'value' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'key' => 'maintenance_mode',
                'value' => 'false',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        SystemStatus::insert([
            [
                'id' => 1,
                'key' => 'app_status',
                'value' => true,
                'message' => 'App is running normally',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'key' => 'maintenance',
                'value' => false,
                'message' => 'App is not under maintenance',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Set up tasks for testing
     */
    protected function setupTasks(): void
    {
        Task::insert([
            [
                'id' => 1,
                'name' => 'Complete Profile',
                'description' => 'Complete your profile information',
                'points' => 100,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Verify Identity',
                'description' => 'Verify your identity with KYC',
                'points' => 200,
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'name' => 'Add Bank Account',
                'description' => 'Add your bank account for withdrawals',
                'points' => 150,
                'position' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Set up tiers for testing
     */
    protected function setupTiers(): void
    {
        Tier::insert([
            [
                'id' => 1,
                'name' => 'Tier 1',
                'title' => '24h Withdrawal Limit',
                'withdrawal_limit' => '500000.00',
                'requirements' => '["Register a KOYN Account"]',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Tier 2',
                'title' => 'Enhanced Withdrawal Limit',
                'withdrawal_limit' => '2000000.00',
                'requirements' => '["Complete KYC Verification"]',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
