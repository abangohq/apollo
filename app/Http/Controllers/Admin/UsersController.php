<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\User\AccountSuspensionNotification;
use App\Repositories\CryptoRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(public UserRepository $userRepo, public CryptoRepository $cryptoRepo)
    {
        //
    }

    /**
     * Get the list of all user on the system
     */
    public function index(Request $request)
    {
        $users = $this->userRepo->users();
        return $this->success($users);
    }

    /**
     * Disable a user acccount
     */
    public function disable(Request $request, User $user)
    {
        $user->update(["status" => 'inactive']);
        $user->notify(new AccountSuspensionNotification('inactive'));
        return $this->success($user, 'User disabled successfully!');
    }

    /**
     * Enable a user account
     */
    public function enable(Request $request, User $user)
    {
        $user->update(["status" => 'active']);
        $user->notify(new AccountSuspensionNotification('active'));

        return $this->success($user, 'User enabled successfully!');
    }

    /**
     * User basic information kyc accounts
     */
    public function basic(Request $request, $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->load(['kycs', 'banks', 'cryptowallets']);
        return $this->success($user);
    }

    /**
     * Retrieve user swap transactions
     */
    public function swapTransactions(Request $request, $user)
    {
        $swaps = $this->cryptoRepo->userSwapTransactions($user);
        return $this->success($swaps, 'Crypto swaps');
    }

    /**
     * Retrieve user wallet withdrawals
     */
    public function withdrawals(Request $request, $user)
    {
        $withdrawals = TransactionRepository::userWithdrawals($user);
        return $this->success($withdrawals, 'Wallet withdrawals');
    }

    /**
     * Retrieve user crypto deposits
     */
    public function deposits(Request $request, $user)
    {
        $swaps = $this->cryptoRepo->userTransactions($user);
        return $this->success($swaps, 'crypto deposits');
    }

    /**
     * Retrieve user bills payments
     */
    public function transactions(Request $request, $user)
    {
        $transactions = TransactionRepository::wallet($user);
        return $this->success($transactions, 'user trasanctions');
    }

    /**
     * Flag a user account for manual review
     */
    public function flag(Request $request, User $user)
    {
        $reason = $request->input('reason');
        $user->update([
            'is_flagged' => true,
            'flag_reason' => $reason
        ]);

        return $this->success($user->fresh(), 'User flagged successfully!');
    }

    /**
     * Remove flag from a user account
     */
    public function unflag(Request $request, User $user)
    {
        $user->update([
            'is_flagged' => false,
            'flag_reason' => null
        ]);

        return $this->success($user->fresh(), 'User unflagged successfully!');
    }
}
