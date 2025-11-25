<?php

namespace App\Jobs;

use App\Models\CryptoWallet;
use App\Services\Crypto\VaultodyService;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateOldWallets implements ShouldQueue
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
                // Check if user has an ETH wallet
                $wallet = DB::table('crypto_wallets')
                    ->where('chain', 'BTC')
                    ->where('user_id', $user->id)
                    // ->whereNull('old_address')
                    ->first();
                    
                if (!$wallet) {
                    continue;
                }
                
                $response = $this->vaultodyService->generateAddress($user->email, 'bitcoin');
                
                if (!isset($response->data->item->address)) {
                    throw new \Exception('Invalid response format: ' . json_encode($response));
                }
                
                // Start transaction for safe update
                DB::beginTransaction();
                
                // First backup current address
                DB::table('crypto_wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'old_address' => $wallet->address
                    ]);
                    
                // Then set new address
                $walletModel = CryptoWallet::find($wallet->id);
                $walletModel->address = $response->data->item->address;
                $walletModel->save();
                    
                DB::commit();
                \Log::info("Successfully updated BTC wallet for user {$user->id}");
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error processing user {$user->id}: {$e->getMessage()}");
            }
        }
    }
}
