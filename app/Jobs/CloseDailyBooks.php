<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;

class CloseDailyBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->queue = 'high-priority';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            $yesterday = Carbon::yesterday()->format('Y-m-d');

            // Get all transactions for the just concluded day
            $dailyTransactions = WalletTransaction::whereDate('created_at', $yesterday)
                ->selectRaw('
                    SUM(CASE WHEN entry = "credit" THEN amount ELSE 0 END) as total_credits,
                    SUM(CASE WHEN entry = "debit" THEN amount ELSE 0 END) as total_debits,
                    SUM(charge) as total_charges,
                    COUNT(*) as total_transactions
                ')
                ->first();

            // Create daily summary record
            DB::table('daily_book_closures')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'date' => $yesterday,
                'total_credits' => $dailyTransactions->total_credits,
                'total_debits' => $dailyTransactions->total_debits,
                'total_charges' => $dailyTransactions->total_charges,
                'total_transactions' => $dailyTransactions->total_transactions,
                'net_position' => $dailyTransactions->total_credits - $dailyTransactions->total_debits,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::commit();

            Log::info('Daily books closed successfully', [
                'date' => $yesterday,
                'total_transactions' => $dailyTransactions->total_transactions
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error closing daily books', [
                'date' => $yesterday,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}
