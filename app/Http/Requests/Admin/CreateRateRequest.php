<?php

namespace App\Http\Requests\Admin;

use App\Models\CryptoRate;
use Illuminate\Foundation\Http\FormRequest;

class CreateRateRequest extends FormRequest
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
            'rate_range' => ['required'],
            'rate' => ['required', 'numeric'],
            'fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'range_start' => ['required', 'numeric'],
            'range_end' => ['required', 'numeric', fn ($attr, $val, $fail) => $this->checkRate($attr, $val, $fail)],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * check no other rate has range_start and range_end
     */
    public function checkRate($attr, $val, $fail)
    {
        $exists = CryptoRate::where('range_start', $this->input('range_start'))
            ->where('range_end', $this->input('range_end'))
            ->exists();

        if ($exists) {
            $fail('The selected range has already been created.');
        }
    }
}
