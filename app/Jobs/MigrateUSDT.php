<?php

namespace App\Jobs;

use App\Services\Crypto\VaultodyService;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateUSDT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $vaultodyService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->vaultodyService = app(VaultodyService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = DB::table('users')->get();
    
        foreach ($users as $user) {
            try {
                // Check if user has a USDT (ERC20) wallet that needs updating
                $usdtWallet = DB::table('crypto_wallets')
                    ->where('chain', 'USDT (ERC20)')
                    ->where('user_id', $user->id)
                    ->whereNull('old_address')
                    ->first();
                    
                if (!$usdtWallet) {
                    continue;
                }
    
                // Check if user has an ETH wallet
                $ethWallet = DB::table('crypto_wallets')
                    ->where('chain', 'ETH')
                    ->where('user_id', $user->id)
                    ->first();
    
                // Start transaction for safe update
                DB::beginTransaction();
    
                if ($ethWallet) {
                    // If ETH wallet exists, use its address
                    // First backup current address
                    DB::table('crypto_wallets')
                        ->where('id', $usdtWallet->id)
                        ->update([
                            'old_address' => $usdtWallet->address
                        ]);
                        
                    // Then set ETH address
                    DB::table('crypto_wallets')
                        ->where('id', $usdtWallet->id)
                        ->update([
                            'address' => $ethWallet->address
                        ]);
    
                    \Log::info("Updated USDT (ERC20) wallet with ETH address for user {$user->id}");
                } else {
                    // If no ETH wallet, generate new address
                    $response = $this->vaultodyService->generateAddress($user->email, 'ethereum');
                    
                    if (!isset($response->data->item->address)) {
                        throw new \Exception('Invalid response format: ' . json_encode($response));
                    }
                    
                    // First backup current address
                    DB::table('crypto_wallets')
                        ->where('id', $usdtWallet->id)
                        ->update([
                            'old_address' => $usdtWallet->address
                        ]);
                        
                    // Then set new address
                    DB::table('crypto_wallets')
                        ->where('id', $usdtWallet->id)
                        ->update([
                            'address' => $response->data->item->address
                        ]);
    
                    \Log::info("Generated new address for USDT (ERC20) wallet for user {$user->id}");
                }
                    
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error processing user {$user->id}: {$e->getMessage()}");
            }
        }
    }
}
