<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends FormRequest
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
            'old_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', fn ($attr, $val, $fail) => $this->redundant($attr, $val, $fail)]
        ];
    }

    /**
     * Check not to use old password as new one
     */
    public function redundant($attr, $val, $fail)
    {
        if (Hash::check($val, $this->user()->password)) {
            $fail('The new password must be different from the old password.');
        }
    }

    /**
     * password attributes to save
     */
    public function passwordAttr()
    {
        return [
            'password' => Hash::make($this->input('password'))
        ];
    }
}
