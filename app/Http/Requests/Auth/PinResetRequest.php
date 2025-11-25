<?php

namespace App\Http\Requests\Auth;

use App\Models\VerifyToken;
use App\Notifications\Auth\PinResetToken;
use App\Support\Utils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class PinResetRequest extends FormRequest
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
            'email' => ['required', 'email', fn ($attr, $val, $fail) => $this->checkMail($attr, $val, $fail)]
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function withValidator($validator)
    {
        $validator->after(fn ($validator) => $this->sendToken());
    }

    /**
     * Check if email is valid to the auth user
     */
    public function checkMail($attr, $val, $fail)
    {
        if ($this->input('email') !== $this->user()->email) {
            return $fail('The selected email is invalid.');
        }
    }

    /**
     * Handle Email verification token
     */
    public function sendToken()
    {
        $token = Utils::generateToken();
        $payload  = [
            'username' => $this->input('username'),
            'token' => $token,
        ];

        VerifyToken::upsert([
            ['email' => $this->input('email'), 'token' => Hash::make($token), 'expires_at' => now()->addMinutes(5)]
        ], ['email'], ['token', 'expires_at']);

        $this->user()->notify(new PinResetToken($payload));
    }
}
