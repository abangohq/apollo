<?php

namespace App\Http\Requests\Kyc;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateKycRequest extends FormRequest
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
            'user_id' =>  [
                'required',
                Rule::exists('users', 'id')
            ],
            'verification_type' => ['required', 'string', new Enum(VerificationType::class)],
            'status' => ['required', 'string', new Enum(VerificationStatus::class)],
        ];
    }
}
