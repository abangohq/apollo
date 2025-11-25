<?php

namespace App\Http\Requests\Admin\Finance;

use App\Enums\Tranx;
use App\Enums\UserType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Http\FormRequest;

class CreateRecRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'user_id' => ['required', fn($attr, $val, $fail) => $this->checkUser($attr, $val, $fail)],
            'transaction_id' => ['nullable', fn($attr, $val, $fail) => $this->tranxCheck($attr, $val, $fail)],
            'entry' => ['required', 'in:credit,debit'],
            'reason' => ['required', 'max:150'],
            'narration' => ['required', 'string', 'max:50']
        ];
    }

    /**
     * Check user exists and is not an admin
     */
    public function checkUser($attr, $val, $fail)
    {
        $user = User::where('id', $val)->first();

        if (is_null($user)) {
            return $fail('The selected user does not exist.');
        }

        if (in_array($user->user_type, [UserType::ADMIN->value, UserType::STAFF->value])) {
            return $fail('You cannot perform a reconciliation action for an administrative account.');
        }

        if ($this->input('entry') == Tranx::DEBIT->value) {
            if ($user->wallet->balance < $this->input('amount')) {
                return $fail('The selected user wallet balance is not enough to perform this action.');
            }
        }
    }

    /**
     * if transaction exists and valid
     */
    public function tranxCheck($attr, $val, $fail)
    {
        if (!isset($val)) {
            return;
        }

        $hasTranx = WalletTransaction::where('id', $val)
            ->where('user_id', $this->input('user_id'))
            ->exists();

        if (!$hasTranx) {
            return $fail('The selected transaction doesnt exist or doesnt belong to the selected user');
        }
    }
}
