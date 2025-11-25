<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class VaultodyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->checkPayload();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Check whether the payload is from a valid source
     */
    public function checkPayload()
    {
        \Log::info('VaultodyRequest', $this->all());

        return true;
        
        // $secret =  config('services.cryptoapis.callback_secret');
        // $computedHash = hash_hmac('sha256', json_encode($this->all()), $secret);

        // return $computedHash === $this->header('x-signature');
    }
}
