<?php

namespace App\Http\Controllers\User;

use App\Actions\Payment\WithdrawAction;
use App\Http\Controllers\Controller;
use App\Notifications\User\WithdrawalNotification;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get the auth user wallet information
     */
    public function walletDetails(Request $request)
    {
        $wallet = $request->user()->wallet;
        return $this->success($wallet, "Wallet fetched successfully.");
    }

    /**
     * withdraw money from auth user wallet
     */
    public function withdraw(WithdrawAction $action)
    {
        $tranx = $action->handle();
        rescue(fn () => $action->request->user()->notify(new WithdrawalNotification($tranx)));
        return $this->success($tranx->load('transactable'));
    }

    /**
     * Get wallet balance for auth user
     */
    public function balance(Request $request)
    {
        $balance = $request->user()->wallet()->first(['balance', 'updated_at']);
        return $this->success($balance, "Wallet balance fetched successfully");
    }

    /**
     * Get user wallet transactions bills|withdrawals
     */
    public function transactions(Request $request)
    {
        $trasactions = TransactionRepository::wallet(auth()->id());
        return $this->success($trasactions);
    }
}
