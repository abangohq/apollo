<?php

namespace App\Http\Requests\Pin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePinRequest extends FormRequest
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
            'pin' => ['required', 'confirmed', 'digits:4'],
            'current_pin' => ['required', 'digits:4', fn ($attr, $val, $fail) => $this->check($attr, $val, $fail)]
        ];
    }

    /**
     * check user current pin if match
     */
    public function check($attr, $val, $fail)
    {
        if (!Hash::check($val, $this->user()->pin)) {
            return $fail("Your current pin is invalid.");
        }

        if (Hash::check($this->input('pin'), $this->user()->pin)) {
            return $fail("You cant reuse your current pin.");
        }

        if (is_null($this->user()->pin)) {
            return $fail("User does not have a PIN");
        }
    }

    /**
     * Pin attributes to save
     */
    public function pinAttributes()
    {
        return [
            'pin' => Hash::make($this->input('pin'))
        ];
    }
}
