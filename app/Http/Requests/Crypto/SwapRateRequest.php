<?php

namespace App\Http\Requests\Crypto;

use App\Services\Crypto\ChangellyService;
use App\Support\Utils;
use App\Traits\RespondsWithHttpStatus;
use Illuminate\Foundation\Http\FormRequest;

class SwapRateRequest extends FormRequest
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
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
            'amountFrom' => ['required', 'numeric'],
        ];
    }
}
