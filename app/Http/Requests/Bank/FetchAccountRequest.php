<?php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FetchAccountRequest extends FormRequest
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
            'account_no' => ['required', 'digits:10'],
            'bank_code' => ['required', 'string']
        ];
    }

    /**
     * Validates and retrieve banks account
     */
    public function retrieveAccount()
    {
        $data = Http::redbiller()->post('/1.0/kyc/bank-account/verify', $this->safe())->json();

        if (isset($data['details']['error'])) {
            throw ValidationException::withMessages([
                'account_number' => 'Account Number does not exist in selected bank'
            ]);
        }

        return $data['details'];
    }
}
