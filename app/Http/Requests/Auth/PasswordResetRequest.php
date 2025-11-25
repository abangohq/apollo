<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Models\VerifyToken;
use App\Notifications\Auth\PasswordResetToken;
use App\Support\Utils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class PasswordResetRequest extends FormRequest
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
            'email' => ['required', 'email', 'exists:users,email']
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
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.exists' => 'Wrong email, please try again!'
        ];
    }

    /**
     * Dispatch notification for token
     */
    public function sendToken()
    {
        $token = Utils::generateToken();
        $payload  = ['token' => $token];
        $user = User::whereEmail($this->input('email'))->first();

        if (is_null($user)) {
            abort(409, 'We are unable to find a user with that email address.');
        }

        VerifyToken::upsert([
            ['email' => $user->email, 'token' => Hash::make($token), 'expires_at' => now()->addMinutes(3)]
        ], ['email'], ['token', 'expires_at']);

        $user->notify(new PasswordResetToken($payload));
    }
}
