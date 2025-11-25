<?php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\ValidationException;

class UpdateUserBankRequest extends FormRequest
{
    use Conditionable;

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
            'account_id' => ['required', 'numeric'],
        ];
    }

    /**
     * Retrieve the bank account information
     */
    public function bankAccount()
    {
        $bank = $this->user()->banks()->whereId($this->account_id)->first();

        if (is_null($bank)) {
            throw ValidationException::withMessages([
                'account_id' => ['The selected bank account does not exist.']
            ]);
        }

        if ($bank->is_primary) {
            throw ValidationException::withMessages([
                'account_id' => ['The selected bank account is already your primary account.']
            ]);
        }

        $primary = $this->user()->banks()->primaryAcct()->first();

        $this->when($primary, fn () => $primary->update(['is_primary' => false]));
        return tap($bank, fn ($bank) => $bank->update(['is_primary' => true]));
    }
}
