<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class DeleteAccountRequest extends FormRequest
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
            'pin' => ['required', 'digits:4', fn ($attr, $val, $fail) => $this->check($attr, $val, $fail)]
        ];
    }

    /**
     * Get the after validation hooks that apply
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->user()->wallet_balance > 100) {
                $validator->errors()->add('pin', 'Kindly withdraw your wallet balance before proceeding');
            }
        });
    }

    /**
     * Check if pin is correct with auth pin
     */
    public function check($attr, $val, $fail)
    {
        if (!Hash::check($val, $this->user()->pin)) {
            return $fail("The selected pin is invalid.");
        }
    }
}
