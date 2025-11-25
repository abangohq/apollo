<?php

namespace App\Http\Requests\Bills;

use App\Rules\BalanceRule;
use Illuminate\Foundation\Http\FormRequest;

class FundBettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'phone_no' => (string) $this->phone_number,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "product" => ['required', 'exists:betting_products,product'],
            "customer_id" => ['required', 'numeric'],
            "amount" => ['required', 'numeric', new BalanceRule],
            "phone_no" => ['required'],
        ];
    }
}
