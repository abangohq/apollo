<?php

namespace App\Http\Requests\Bills;

use App\Services\Payment\RedbillerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class VerifyBettingAccountRequest extends FormRequest
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
            'product' => ['required', 'exists:betting_products,product'],
            'customer_id' => ['required', 'numeric']
        ];
    }

    /**
     * Validate beting account with api provider
     */
    public function checkAccount()
    {
        $response = rescue(fn () => (new RedbillerService)->verifyBettingAccount($this->validated()));

        if (is_null($response)) {
            abort(409, 'We unable to retrieve betting account information at the moment please retry in few minutes.');
        }

        if ($response->response !== 200) {
            abort(409, 'We unable to retrieve betting account information please make sure supplied information is correct.');
        }

        return $response->details;
    }
}
