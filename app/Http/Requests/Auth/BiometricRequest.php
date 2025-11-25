<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class BiometricRequest extends FormRequest
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
            'hash' => ['required', 'string'],
            'type' => ['required', 'in:face,touch']
        ];
    }

    /**
     * prepare the has to save
     */
    public function biometric(): array
    {
        $type = $this->input('type');
        $hash = $this->input('hash');

        return collect(['has_biometric' => true])
            ->when($type == 'face')->merge(['face_id' => $hash])
            ->when($type == 'touch')->merge(['touch_id' => $hash])
            ->toArray();
    }
}
