<?php

namespace App\Http\Requests\Bills;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseMeterRequest extends FormRequest
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
            'product' => ['required', 'string', 'in:Abuja,Eko,Enugu,Jos,Ibadan,Ikeja,Kaduna,Kano,Portharcourt,Benin'],
            "meter_no" => ['required', 'min_digits:10'],
            "meter_type" => ['required', 'string', 'in:PREPAID,POSTPAID'],
            'customer_name' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:500', fn($attr, $val, $fail) => $this->checkBalance($attr, $val, $fail)]
        ];
    }

    /**
     * check if balance is sufficient to make purchase
     */
    public function checkBalance($attr, $val, $fail)
    {
        if ($val > $this->user()->wallet->balance) {
            $fail('Your available balance is insufficient to make purchase.');
        }
    }
}
