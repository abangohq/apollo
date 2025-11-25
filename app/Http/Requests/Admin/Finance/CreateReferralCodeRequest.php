<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CreateReferralCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'unique:referral_codes,code'],
            'reward_amount' => ['nullable', 'numeric'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * Rreferral code attributes to save
     */
    public function refAttributes()
    {
        return collect($this->safe())->merge(['staff_id' => $this->user()->id])
            ->toArray();
    }
}
