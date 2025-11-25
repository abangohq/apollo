<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
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
            'status' => ['required', 'string'],
            'logo' => ['nullable', 'image'],
        ];
    }

    /**
     * Get the attributes to save
     */
    public function assetAttributes()
    {
        $attributes = collect($this->safe())->except('logo');

        if ($this->hasFile('logo')) {
            $attributes->put('logo', $this->logo->storeOnCloudinaryAs('crypto')->getSecurePath());
        }

        return $attributes->toArray();
    }
}
