<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BalanceVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyUserBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-balances 
                            {--user-id= : Verify balance for a specific user ID}
                            {--email= : Verify balance for a specific user email}
                            {--suspend : Automatically suspend users with balance discrepancies}
                            {--limit=100 : Number of users to check (default: 100)}
                            {--all : Check all users (ignores limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify user wallet balances against transaction history and optionally suspend users with discrepancies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $balanceService = new BalanceVerificationService();
        
        $this->info('Starting user balance verification...');
        
        // Get users to check
        $users = $this->getUsersToCheck();
        
        if ($users->isEmpty()) {
            $this->warn('No users found to verify.');
            return 0;
        }
        
        $this->info("Checking balances for {$users->count()} users...");
        
        $results = [
            'total_checked' => 0,
            'balanced' => 0,
            'discrepancies' => 0,
            'suspended' => 0,
            'errors' => 0
        ];
        
        $discrepancies = [];
        
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();
        
        foreach ($users as $user) {
            try {
                $results['total_checked']++;
                
                $balanceCheck = $balanceService->verifyUserBalance($user);
                
                if ($balanceCheck['is_balanced']) {
                    $results['balanced']++;
                } else {
                    $results['discrepancies']++;
                    $discrepancies[] = $balanceCheck;
                    
                    // Auto-suspend if option is enabled
                    if ($this->option('suspend')) {
                        if ($balanceService->suspendUser($user, $balanceCheck)) {
                            $results['suspended']++;
                        }
                    }
                }
                
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Error verifying user balance', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->displayResults($results, $discrepancies);
        
        return 0;
    }
    
    /**
     * Get users to check based on command options
     */
    private function getUsersToCheck()
    {
        if ($this->option('user-id')) {
            return User::where('id', $this->option('user-id'))->get();
        }
        
        if ($this->option('email')) {
            return User::where('email', $this->option('email'))->get();
        }
        
        $query = User::where('status', 'active')->with('wallet');
        
        if ($this->option('all')) {
            return $query->get();
        }
        
        return $query->limit($this->option('limit'))->get();
    }
    
    /**
     * Display verification results
     */
    private function displayResults(array $results, array $discrepancies)
    {
        $this->info('Balance Verification Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Checked', $results['total_checked']],
                ['Balanced', $results['balanced']],
                ['Discrepancies', $results['discrepancies']],
                ['Suspended', $results['suspended']],
                ['Errors', $results['errors']]
            ]
        );
        
        if (!empty($discrepancies)) {
            $this->warn("\nUsers with Balance Discrepancies:");
            
            $discrepancyData = [];
            foreach ($discrepancies as $discrepancy) {
                $discrepancyData[] = [
                    $discrepancy['user_email'],
                    number_format($discrepancy['expected_balance'], 2),
                    number_format($discrepancy['current_balance'], 2),
                    number_format($discrepancy['difference'], 2)
                ];
            }
            
            $this->table(
                ['User Email', 'Expected Balance', 'Current Balance', 'Difference'],
                $discrepancyData
            );
            
            if (!$this->option('suspend')) {
                $this->warn('\nTo automatically suspend users with discrepancies, run with --suspend option.');
            }
        } else {
            $this->info('\nAll checked users have balanced accounts! ✅');
        }
        
        // Log summary
        Log::info('Balance verification completed', $results);
    }
}