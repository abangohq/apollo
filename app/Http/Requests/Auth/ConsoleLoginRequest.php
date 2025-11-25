<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConsoleLoginRequest extends FormRequest
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
            'password' => ['required', 'string']
        ];
    }

    /**
     * Password check if password is correct and throttle.
     */
    public function checkedUser(): User
    {
        $user = User::whereEmail($this->input('email'))
            ->whereIn('user_type', ['admin', 'staff'])->first();

        if (is_null($user)) {
            throw ValidationException::withMessages([
                'email' => 'No record found. Enter a valid credentials.'
            ]);
        }

        if (!Hash::check($this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid credentials, please enter a valid login details'
            ]);
        }

        return $user;
    }

    /**
     * Federate the user into our application
     */
    public function federate(User $user): array
    {
        $user->makeVisible(['user_type', 'role']);
        
        return [
            'token' => $user->createToken('authToken')->plainTextToken,
            'user' =>  new UserResource($user)
        ];
    }
}
