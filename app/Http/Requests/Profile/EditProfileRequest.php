<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class EditProfileRequest extends FormRequest
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
            'phone' => ['nullable', 'digits:11', 'unique:users,phone,'. $this->user()->id],
            'avatar' => ['nullable', 'string']
        ];
    }

    /**
     * Retrieve validated information
     */
    public function profileAttributes()
    {
       return $this->safe()->all();
    }
}
