<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Finance\ReconcileAction;
use App\Actions\Payment\ApproveWithdrawAction;
use App\Actions\Payment\DeclineWithdrawAction;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    /**
     * Get the list of withdrawal
     */
    public function index(Request $request)
    {
        $withdraws = TransactionRepository::withdraws();
        return $this->success($withdraws);
    }

    /**
     * Aprove manual withdrawal request 
     */
    public function approve(ApproveWithdrawAction $action)
    {
        $withdraw = $action->handle();
        return $this->success($withdraw, 'Transaction has been queued and processing.');
    }

    /**
     * Decline withdraw request
     */
    public function decline(DeclineWithdrawAction $action)
    {
        $withdraw = $action->handle();
        return $this->success($withdraw, 'Withdrawal request has been declined successfully!');
    }

    /**
     * Change the withdrawal status
     */
    public function changeStatus(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['status' => ['required', 'in:pending,successful,failed']]);

        abort_if(in_array($withdrawal->status, ['pending', 'successful']), 400, 'You cant change the status of the selected withdrawal');

        $withdrawal->update($validated);
        return $this->success($withdrawal, "Withdrawal status updated successfully.");
    }

    /**
     * Get settlement completed by an employee
     */
    public function employeeWithdrawal(Request $request)
    {
        $withdrawals = TransactionRepository::settleByEmployee($request->userId);
        return $this->success($withdrawals);
    }

    /**
     * Get reconciliations transactions
     */
    public function reconciliations(Request $request)
    {
        $transactions = TransactionRepository::reconciliations();
        return $this->success($transactions);
    }

    /**
     * Create a reconciliations transaction
     */
    public function createReconciliation(ReconcileAction $action)
    {
        $transaction = $action->handle();
        return $this->success($transaction);
    }

    /**
     * Retrieve reconciliable transactions
     */
    public function reconciliableTranx(Request $request)
    {
        $transactions = TransactionRepository::reconciliableTransaction();
        return $this->success($transactions);
    }
}
