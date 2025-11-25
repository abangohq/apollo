<?php

namespace App\Http\Requests\Bills;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AirtimeTopupRequest extends FormRequest
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
            'network' => ['required', 'in:Airtel,MTN,Glo,9mobile'],
            'amount' => ['required', 'numeric', 'min:100', fn ($attr, $val, $fail) => $this->checkBalance($attr, $val, $fail)],
            'phone_no' => ['required', 'numeric'],
        ];
    }

    /**
     * check if user wallet balance can make purchase
     */
    public function checkBalance($attr, $val, Closure $fail)
    {
        if ($val > $this->user()->wallet->balance) {
            $fail('Your available balance is insufficient to complete this transaction.');
        }
    }
}
