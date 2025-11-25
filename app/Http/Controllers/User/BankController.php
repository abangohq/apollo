<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\AddUserBankRequest;
use App\Http\Requests\Bank\FetchAccountRequest;
use App\Http\Requests\Bank\UpdateUserBankRequest;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    /**
     * Get the list of system bank to show
     */
    public function banks(Request $request)
    {
        $banks = Bank::where('status', 'active')->orderBy('bank_name')->get();
        return $this->success($banks, 'Banks fetched successfully');
    }

    /**
     * Create a new bank for user
     */
    public function create(AddUserBankRequest $request)
    {
        $attributes = $request->bankAttributes();
        $account = $request->saveBank($attributes);
        return $this->success($account, "Bank account added successfully.");
    }

    /**
     * Check validity of account number
     */
    public function verifyBankAccount(FetchAccountRequest $request)
    {
        $account = $request->retrieveAccount();
        return $this->success($account, 'Account retrieved successfully.');
    }

    /**
     * Delete bank account for user
     */
    public function destroy(Request $request)
    {
        $bank = $request->user()->banks()->whereId($request->bank)->firstOrFail();
        abort_unless((bool) $bank->delete(), 409, 'Error occured while removing account.');
        return $this->success(null, 'Bank removed successfully.');
    }

    /**
     * Update user account set primary account
     */
    public function update(UpdateUserBankRequest $request)
    {
        $bank = $request->bankAccount();
        return $this->success($bank, "Bank details updated successfully.");
    }

    /**
     * Get the bank accounts for the user
     */
    public function accounts(Request $request)
    {
        $banks = $request->user()->banks;
        return $this->success($banks, 'Bank accounts fetched successfully.');
    }
}
