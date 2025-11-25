<?php

namespace App\Http\Requests\Bills;

use App\Services\Payment\RedbillerService;
use Illuminate\Foundation\Http\FormRequest;

class VerifyWifiRequest extends FormRequest
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
            'product' => ['required', 'in:Smile,Spectranet'],
            'device_no' => ['required', 'numeric']
        ];
    }

    /**
     * Validate beting account with api provider
     */
    public function checkDevice()
    {
        $response = rescue(fn () => (new RedbillerService)->verifyDeviceNumber($this->validated()));

        if (is_null($response)) {
            abort(409, 'We unable to retrieve betting account information at the moment please retry in few minutes.');
        }

        if ($response->response !== 200) {
            abort(409, 'We unable to retrieve betting account information please make sure supplied information is correct.');
        }

        return $response->details;
    }
}
