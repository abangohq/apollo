<?php

namespace App\Services;

use App\Enums\Tranx;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;

class BalanceVerificationService
{
    /**
     * Verify if user's credits and debits tally with their current wallet balance
     *
     * @param User $user
     * @return array
     */
    public function verifyUserBalance(User $user): array
    {
        // Define the start date for verification period
        $startDate = '2025-09-12';

        // Get all successful wallet transactions for the user from the specified date
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->where('status', Tranx::TRANX_SUCCESS->value)
            // ->where('transaction_type', '!=', Tranx::RECONCILE->value)
            ->whereDate('created_at', '>=', $startDate)
            ->get();

        // Calculate total credits and debits
        $totalCredits = $transactions->where('entry', Tranx::CREDIT->value)->sum('amount');
        $totalDebits = $transactions->where('entry', Tranx::DEBIT->value)->sum('amount');

        // Get current wallet balance from the database
        $currentBalance = $user->wallet()->value('balance') ?? 0;

        // Get the wallet balance from the latest successful transaction before the start date
        $previousBalance = WalletTransaction::where('user_id', $user->id)
            ->where('status', Tranx::TRANX_SUCCESS)
            ->where('is_reversal', false)
            ->whereDate('created_at', '<', $startDate)
            ->orderBy('created_at', 'desc')
            ->value('wallet_balance') ?? 0;

        // Calculate expected balance: previous balance + credits since start date - debits since start date
        $expectedBalance = $previousBalance + $totalCredits - $totalDebits;

        // Check if balances match (allowing for small floating point differences)
        $balanceDifference = abs($expectedBalance - $currentBalance);
        $isBalanced = $balanceDifference < 100; // Allow 100 Naira difference for floating point precision

        $result = [
            'is_balanced' => $isBalanced,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'expected_balance' => $expectedBalance,
            'current_balance' => $currentBalance,
            'difference' => $balanceDifference,
            'user_id' => $user->id,
            'user_email' => $user->email
        ];

        // Log the verification result
        Log::info('Balance verification completed', $result);

        return $result;
    }

    /**
     * Suspend user account due to balance discrepancy
     *
     * @param User $user
     * @param array $balanceData
     * @return bool
     */
    public function suspendUser(User $user, array $balanceData): bool
    {
        try {
            $user->update(['status' => 'inactive']);

            // Log the suspension
            Log::critical('User suspended due to balance discrepancy', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'balance_data' => $balanceData,
                'suspended_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to suspend user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'balance_data' => $balanceData
            ]);

            return false;
        }
    }

    /**
     * Get detailed transaction breakdown for a user
     *
     * @param User $user
     * @return array
     */
    public function getTransactionBreakdown(User $user): array
    {
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->where('status', Tranx::TRANX_SUCCESS)
            ->where('is_reversal', false)
            ->whereDate('created_at', '>=', '2025-09-13')
            ->with('transactable')
            ->orderBy('created_at', 'desc')
            ->get();

        $breakdown = [
            'total_transactions' => $transactions->count(),
            'credits' => [],
            'debits' => [],
            'summary' => []
        ];

        // Group by transaction type
        $creditsByType = $transactions->where('entry', Tranx::CREDIT->value)
            ->groupBy('transaction_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount')
                ];
            });

        $debitsByType = $transactions->where('entry', Tranx::DEBIT->value)
            ->groupBy('transaction_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount')
                ];
            });

        $breakdown['credits'] = $creditsByType->toArray();
        $breakdown['debits'] = $debitsByType->toArray();

        $breakdown['summary'] = [
            'total_credits' => $transactions->where('entry', Tranx::CREDIT->value)->sum('amount'),
            'total_debits' => $transactions->where('entry', Tranx::DEBIT->value)->sum('amount'),
            'net_balance' => $transactions->where('entry', Tranx::CREDIT->value)->sum('amount') -
                           $transactions->where('entry', Tranx::DEBIT->value)->sum('amount')
        ];

        return $breakdown;
    }
}
