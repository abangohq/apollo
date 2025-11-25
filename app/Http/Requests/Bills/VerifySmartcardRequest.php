<?php

namespace App\Http\Requests\Bills;

use App\Services\Payment\RedbillerService;
use Illuminate\Foundation\Http\FormRequest;

class VerifySmartcardRequest extends FormRequest
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
            "smart_card_no" => ['required', 'numeric'],
        ];
    }

    /**
     * Check if smart card is valid
     */
    public function checkCard()
    {
        try {
            $response = (new RedbillerService)->verifySmartCardNumber($this->validated());

            if($response->response !== 200) {
                abort(409, 'We unable to retrieve card information please make sure iuc number is correct.');
            }

            return $response->details;
        } catch (\Throwable $th) {
            abort(409, 'We unable to retrieve smart card information at the moment please retry in few minutes.');
        }
    }
}
