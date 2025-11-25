<?php

namespace App\Http\Requests\Crypto;

use App\Models\CryptoAsset;
use App\Services\Crypto\ChangellyService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CreateSwapRequest extends FormRequest
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
            'from' => ['required', 'string', fn ($attr, $val, $fail) => $this->validCurrency($attr, $val, $fail)],
            'to' => ['required', 'string'],
            'amountFrom' => ['required', 'numeric'],
            'swap_type' => ['required', 'string', 'in:fixed,floating'],
            'address' => ['present', 'nullable'],
            'refundAddress' => ['required_if:swap_type,fixed', 'string'],
            'rateId' => ['required_if:swap_type,fixed'],
            'app_address' => ['required', 'boolean', fn ($attr, $val, $fail) => $this->appAddress($attr, $val, $fail)]
        ];
    }

    /**
     * check if the from and to currency is available for swap
     */
    public function validCurrency($attr, $val, Closure $fail)
    {
        $swapPairs = collect((new ChangellyService)->swapPairs($this->from)['result']);

        $hasPair = $swapPairs->first(function ($pair) {
            return strtolower($pair['from']) == strtolower($this->from) &&
                strtolower($pair['to']) == strtolower($this->to);
        });

        if (is_null($hasPair)) {
            return $fail("The selected currency pair is not available for swap.");
        }
    }

    /**
     * App address validation
     */
    public function appAddress($attr, $value, Closure $fail)
    {
        $appName = env('APP_NAME');

        if (empty($this->address) && !$value) {
            return $fail("The {$appName} address must be selected if the recipient address field is empty.");
        }

        // app address check if we support currency
        if (empty($this->address) && $value) {
            $hasSupport = CryptoAsset::where('symbol', $this->to)->exists();

            if (!$hasSupport) {
                return $fail("We do not support the selected currency for swap exchange when {$appName} wallet is selected.");
            }
        }

        if (isset($this->address) && $value) {
            return $fail("You can only use one address option. please check your selection");
        }
    }
}
