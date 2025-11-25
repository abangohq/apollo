<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBetProductRequest extends FormRequest
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
            'product' => ['required', 'string'],
            'maximum_amount' => ['nullable', 'numeric'],
            'minimum_amount' => ['nullable', 'numeric'],
            'status' => ['required', 'string'],
            'logo' => ['nullable', 'image'],
        ];
    }

    /**
     * Get the attributes to save
     */
    public function productAttributes()
    {
        $attributes = collect($this->safe())->except('logo');

        if ($this->hasFile('logo')) {
            $result = $this->logo->storeOnCloudinaryAs('bills')->getSecurePath();
            $attributes->put('logo', $result);
        }

        return $attributes->toArray();
    }
}
