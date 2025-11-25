<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class CreateStaffRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', fn ($attr, $val, $fail) => $this->mailUnique($attr, $val, $fail)],
            'role' => ['required', 'string', 'in:accountant,announcer,marketer,support'],
            'password' => ['required', 'string']
        ];
    }

    /**
     * check if email has not been picked
     */
    public function mailUnique($attr, $val, $fail)
    {
        $picked = User::where('email', $val)->orWhere(fn ($q) => $q->where('username', $val))->exists();

        if ($picked) {
            $fail('The selected email has already been picked.');
        }
    }

    /**
     * Get the attributes to save
     */
    public function userAttributes()
    {
        return collect($this->safe())->except('password')
            ->merge([
                'password' => Hash::make($this->input('password')),
                'user_type' => 'staff'
            ])
            ->toArray();
    }
}
