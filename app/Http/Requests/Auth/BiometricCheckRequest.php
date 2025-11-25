<?php

namespace App\Http\Requests\Auth;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class BiometricCheckRequest extends FormRequest
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
            'hash' => ['required', 'string'],
            'type' => ['required', 'in:face,touch', fn ($attr, $val, $fail) => $this->hashCheck($attr, $val, $fail)]
        ];
    }

    /**
     * Validate if has is correct
     */
    public function hashCheck($attr, $val, Closure $fail)
    {
        if ($val == 'face' && request()->user()->face_id != $this->input('hash')) {
            return $fail('We are unable to confirm your facial biometric');
        }

        if ($val == 'touch' && request()->user()->touch_id != $this->input('hash')) {
            return $fail('We are unable to confirm your touch biometric');
        }
    }
}
