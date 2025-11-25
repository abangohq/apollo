<?php

namespace App\Http\Requests\Auth;

use App\Models\UserData;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class PinLoginRequest extends FormRequest
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
            'pin' => ['required', 'digits:4', fn ($attr, $val, $fail) => $this->hashCheck($attr, $val, $fail)],
        ];
    }

    /**
     * Get the after Validation  hook that should apply
     */
    public function withValidator($validator)
    {
        $validator->after(
            fn ($val) => rescue(fn () => $this->logSource())
        );
    }

    /**
     * Validate if has is correct
     */
    public function hashCheck($attr, $val, Closure $fail)
    {
        if (is_null($this->user()->pin) || empty($this->user()->pin)) {
            return $fail('You need to setup your transaction pin in order to login with pin.');
        }

        if (!Hash::check($this->input('pin'), $this->user()->pin)) {
            return $fail('The selected pin is invalid.');
        }
    }

    /**
     * Log an ip data for the user authentication
     */
    public function logSource()
    {
        UserData::create(
            [
                'user_id' => auth()->id(),
                'user_agent' => $this->server('HTTP_USER_AGENT'),
                'ip_address' => $this->headers->all("cf-connecting-ip")[0] ?? $this->getClientIp()
            ]
        );

        return $this;
    }
}
