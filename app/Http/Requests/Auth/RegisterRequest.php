<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\UserResource;
use App\Models\ReferralCode;
use App\Models\Task;
use App\Models\User;
use App\Models\VerifyToken;
use App\Models\Wallet;
use App\Notifications\Auth\VerificationToken;
use App\Notifications\Auth\WelcomeGreeting;
use App\Support\Utils;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegisterRequest extends FormRequest
{
    use Conditionable;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'phone' => (string) $this->phone,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        switch (request()->route('step')) {
            case 'step-1':
                return [
                    'name' => ['nullable', 'string'],
                    'email' => ['required', 'email', 'unique:users'],
                    'username' => [
                        'required',
                        'string',
                        'min:3',
                        'max:20',
                        'regex:/^[a-zA-Z0-9_\-]+$/',
                        Rule::unique('users', 'username'),
                        Rule::notIn(User::getReservedUsernames()),
                    ],
                ];
            case 'step-2':
                return [
                    'email' => ['required', 'email'],
                    'token' => ['required', 'numeric', fn($attr, $val, $fail) => $this->checkToken($attr, $val, $fail)]
                ];
            case 'step-3':
                return [
                    'phone' => ['required', 'digits:11', 'unique:users'],
                ];
            case 'step-4':
                return [
                    'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers(), 'confirmed'],
                ];
            case 'step-5':
                return [
                    'name' => ['nullable', 'string'],
                    'email' => ['required', 'email', 'unique:users'],
                    'username' => [
                        'required',
                        'string',
                        'min:3',
                        'max:20',
                        'regex:/^[a-zA-Z0-9_\-]+$/',
                        Rule::unique('users', 'username'),
                        Rule::notIn(User::getReservedUsernames()),
                    ],
                    'phone' => ['required', 'digits:11', 'unique:users'],
                    'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
                    'heard_about_us' => ['nullable', 'max:255'],
                    'referral_code' => ['nullable', fn($attr, $val, $fail) => $this->referralCheck($attr, $val, $fail)],
                    'avatar' => ['nullable', 'string'],
                ];
            default:
                throw new NotFoundHttpException();
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        switch (request()->route('step')) {
            case 'step-1':
                return [
                    'username.unique' => 'Username already exists. Please try again.',
                    'phone.unique' => 'Phone number already exists. Please try again.',
                    'email.unique' => 'Email already exists. Please try again.',
                ];
            case 'step-3':
                return [
                    'phone.unique' => 'Phone number already exists. Please try again.',
                ];
            default:
                return [];
                break;
        }
    }

    /**
     * Check referral code is valid
     */
    public function referralCheck($attr, $val, $fail)
    {
        if ($val) {
            $code = ReferralCode::where('code', $val)->whereActive(true)->first();

            if (is_null($code)) {
                return $fail('The selected referral code is invalid or no longer active.');
            }
        }
    }

    /**
     * Check email token is valid
     */
    public function checkToken($attr, $value, Closure $fail)
    {
        $token = VerifyToken::tokenMail($this->input('email'))->first();

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

        rescue(fn() => Notification::route('mail', $this->input('email'))->notify(new VerificationToken($payload)));
    }

    /**
     * User attributess to save
     */
    public function userAttributes()
    {
        $attributes = collect($this->safe())->except('username', 'referral_code')
            ->merge([
                'referral_code' => Utils::referralCode(),
                'username' => Str::lower($this->input('username')),
                'password' => Hash::make($this->input(['password'])),
                'email_verified_at' => now(),
                'tier_id' => 1,
            ]);

        return $attributes->toArray();
    }
}
