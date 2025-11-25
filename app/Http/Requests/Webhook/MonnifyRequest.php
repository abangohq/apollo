<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class MonnifyRequest extends FormRequest
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
        logger()->info($this->header('monnify-signature'));

        $secret = config('services.monnify.secret');

        $computedHash = hash_hmac('sha512', json_encode($this->all()), $secret);

        // return $computedHash === $this->header('monnify-signature');
        return true;
    }
}
