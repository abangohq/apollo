<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
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
        ];
    }

    /**
     * check if email has not been picked
     */
    public function mailUnique($attr, $val, $fail)
    {
        $picked = User::where('email', $val)
            ->where('id', '!=', $this->route()->user->id)
            ->exists();

        if ($picked) {
            $fail('The selected email has already been picked.');
        }
    }
}
