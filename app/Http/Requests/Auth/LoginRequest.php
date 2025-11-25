<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'min:3'],
            'password' => ['required', 'string'],
            // optional device info to update on successful login
            'device_token' => ['sometimes', 'string'],
            'device_type' => ['sometimes', 'in:android,ios'],
        ];
    }

    /**
     * Password check if password is correct and throttle.
     */
    public function checkedUser(): User
    {
        $user = User::whereEmail($this->input('email'))
            ->orWhere('username', $this->input('email'))->first();

        if (is_null($user)) {
            throw ValidationException::withMessages([
                'email' => 'No record found. Enter a valid credentials.'
            ]);
        }

        if ($user->hasExceededLoginAttempts()) {
            abort(403, 'you have exceeded the allowed login attempts, please reset your password');
        }

        if (!Hash::check($this->input('password'), $user->password)) {
            rescue(fn() => $user->increment('failed_login_attempts'));

            throw ValidationException::withMessages([
                'password' => 'Wrong login credentials. Enter a valid credentials.'
            ]);
        }

        if (is_null($user->email_verified_at)) {
            abort(401, 'You need to verify your registration token in order to complete your login.');
        }

        return $user;
    }

    /**
     * Federate the user into our application
     */
    public function federate(User $user): array
    {
        rescue(fn() => $user->resetFailedAttempts());
        
        // Update device token/type if provided
        rescue(function () use ($user) {
            $attrs = [];
            if ($this->filled('device_token')) {
                $attrs['device_token'] = $this->input('device_token');
            }
            if ($this->filled('device_type')) {
                $attrs['device_type'] = $this->input('device_type');
            }
            if (!empty($attrs)) {
                $user->update($attrs);
            }
        });
        
        return [
            'token' => $user->createToken('authToken')->plainTextToken,
            'user' =>  new UserResource($user->refresh())
        ];
    }
}
