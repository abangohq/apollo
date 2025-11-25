<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CryptoWithdrawalRequest extends FormRequest
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
            'amount' => ['required', 'numeric', fn($attr, $val, $fail) => $this->checkAmount($attr, $val, $fail)],
            'priority' => ['required', 'in:slow,standard,fast'],
        ];
    }

    /**
     * check amount
     */
    public function checkAmount($attr, $val, $fail)
    {
        if ($val < 0.00000546) {
            return $fail('Amount for destination must be greater than 0.00000546');
        }
    }
}
