<?php

namespace App\Http\Requests\Auth;

use App\Models\VerifyToken;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreatePasswordRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'token' => ['required', 'numeric', fn($attr, $val, $fail) => $this->checkToken($attr, $val, $fail)],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()]
        ];
    }

    /**
     * Check email token is valid
     */
    public function checkToken($attr, $value, Closure $fail)
    {
        $token = VerifyToken::whereEmail($this->input('email'))->valid()->first();

        if (!$token) {
            $fail('Invalid OTP Code. Please try again!');
            return;
        }

        if (!Hash::check($value, $token->token)) {
            $fail('Invalid OTP Code. Please try again!');
            return;
        }
    }

    /**
     * Password attributes to save
     */
    public function passwordAttr()
    {
        rescue(fn() => VerifyToken::whereEmail($this->input('email'))->delete());

        return [
            'password' => Hash::make($this->input('password')),
            'failed_login_attempts' => 0
        ];
    }
}
