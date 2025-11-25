<?php

namespace App\Http\Requests\Wallet;

use App\Enums\Status;
use App\Models\SystemStatus;
use App\Models\Tier;
use App\Rules\BankRule;
use App\Rules\PinRule;
use App\Support\LogAction;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class WithdrawalRequest extends FormRequest
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
            'pin' => ['required', 'digits:4', new PinRule],
            'amount' => [
                'required',
                'numeric',
                fn($attr, $val, $fail) => $this->amountCheck($attr, $val, $fail),
                fn($attr, $val, $fail) => $this->limitCheck($attr, $val, $fail)
            ],
            'bank_id' => [
                'required',
                'numeric',
                new BankRule,
                fn($attr, $val, $fail) => $this->timeWindowCheck($attr, $val, $fail)
            ]
        ];
    }

    /**
     * Amount to withdraw validation
     */
    public function amountCheck($attr, $val, Closure $fail)
    {
        if ($this->user()->wallet->balance < $val) {
            return $fail('Your available balance is insufficient to complete this transaction.');
        }

        if ($val < 1000) {
            return $fail('The minimum amount withdrawal request is N1000.');
        }
    }

    /**
     * Withdrawal limit validation
     */
    public function limitCheck($attr, $val, Closure $fail)
    {
        $systemStatus = SystemStatus::where('key', 'withdrawal')->first();

        if (!@$systemStatus?->value) {
            return $fail(is_null($systemStatus?->message) ? 'We are unble to process your transaction at the moment.' : $systemStatus->message);
        }

        $dayWithdrawn = $this->user()->withdrawals()
            ->whereNotIn('status', [Status::REJECTED->value, Status::FAILED->value])
            ->whereDate('created_at', now()->today())->sum('amount');

        $limit = Tier::where('id', intval($this->user()->tier_id))->first()->withdrawal_limit;

        $limitRemain = $limit - $dayWithdrawn;

        if ($limit != null and $limitRemain < $this->input('amount')) {
            LogAction::withdrawLimit($this->input('amount'));
            $fail('You have reached your daily withdrawal limit. Upgrade your KYC to increase your daily limit.');
        }
    }

    /**
     * Time window check for withdrawal check
     */
    public function timeWindowCheck($attr, $val, Closure $fail)
    {
        $timeFrame = now()->subMinutes(5);

        $exists = $this->user()->withdrawals()
            ->where('created_at', '>=', $timeFrame)->exists();

        if ($exists) {
            $fail('You have recently made a withdrawal request. Please wait before making another request.');
        }
    }
}
