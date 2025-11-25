<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class XprocessingRequest extends FormRequest
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
        \Log::info('Processing Received', $this->all());

        $payload = $this->all();
        $signature = $this->input('Signature');

        if (app()->environment('production')) {

            $password = config('services.xprocess.password');
            $hash = md5("{$payload['PaymentId']}:{$payload['MerchantId']}:{$payload['Email']}:{$payload['Currency']}:{$password}");

            return $hash === $signature;
        }

        return true;
    }
}
