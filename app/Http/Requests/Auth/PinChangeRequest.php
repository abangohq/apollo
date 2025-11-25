<?php

namespace App\Http\Requests\Auth;

use App\Models\VerifyToken;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class PinChangeRequest extends FormRequest
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
            'pin' => ['required', 'digits:4'],
            'token' => ['required', 'numeric', fn($attr, $val, $fail) => $this->checkToken($attr, $val, $fail)]
        ];
    }

    /**
     * Check email token is valid
     */
    public function checkToken($attr, $value, Closure $fail)
    {
        if ($this->user()->pin == null) {
            return $fail('User doesnt have a pin.');
        }

        $token = VerifyToken::whereEmail(request()->user()->email)->valid()->first();

        if (!$token) {
            return $fail('Invalid OTP Code. Please try again!');
        }

        if (!Hash::check($value, $token->token)) {
            return $fail('Invalid OTP Code. Please try again!');
        }
    }

    /**
     * Attributes to save
     */
    public function pinAttributes(): array
    {
        return  [
            'pin' => Hash::make($this->input('pin'))
        ];
    }
}
