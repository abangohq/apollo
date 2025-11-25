<?php

namespace App\Http\Requests\Bills;

use Illuminate\Foundation\Http\FormRequest;

class CableTopupRequest extends FormRequest
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
            "product" => ['required', 'in:DStv,GOtv,StarTimes'],
            "code" => ['required'],
            "smart_card_no" => ['required', 'numeric'],
            'customer_name' => ['required', 'string'],
        ];
    }
}
