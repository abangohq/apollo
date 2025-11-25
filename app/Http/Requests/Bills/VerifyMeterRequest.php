<?php

namespace App\Http\Requests\Bills;

use App\Services\Payment\RedbillerService;
use Illuminate\Foundation\Http\FormRequest;

class VerifyMeterRequest extends FormRequest
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
            'product' => ['required', 'string', 'in:Abuja,Eko,Enugu,Jos,Ibadan,Ikeja,Kaduna,Kano,Portharcourt,Benin'],
            "meter_no" => ['required', 'min_digits:10'],
            "meter_type" => ['required', 'string', 'in:PREPAID,POSTPAID']
        ];
    }

    /**
     * check if the meter nunber is valid
     */
    public function checkMeterNunber()
    {
        try {
            $response = (new RedbillerService)->verifyMeterNumber($this->validated());

            if($response->response !== 200) {
                abort(409, 'We unable to retrieve card information please make sure iuc number is correct.');
            }

            return $response->details;
        } catch (\Throwable $th) {
            abort(409, 'We unable to retrieve smart card information at the moment please retry in few minutes.');
        }
    }
}
