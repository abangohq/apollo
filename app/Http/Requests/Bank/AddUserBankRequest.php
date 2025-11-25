<?php

namespace App\Http\Requests\Bank;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Task;
use App\Support\Utils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddUserBankRequest extends FormRequest
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
            'bank_id' => ['required', 'exists:banks,id', fn ($attr, $val, $fail) => $this->check($attr, $val, $fail)],
            'account_name' => ['required', 'string',],
            'account_number' => ['required', 'numeric', 'digits:10']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'bank_id.exists' => ['The selected banks is invalid.']
        ];
    }

    /**
     * Check the numbers of banks user has
     */
    public function check($attr, $val, $fail)
    {
        $bankCount = $this->user()->banks()->count();

        if ($bankCount >= 5) {
            return $fail("You have reached the maximum limit of 5 banks. Please delete a bank before adding a new one.");
        }

        $exists = $this->user()->banks()->whereAccountNumber($this->account_number)->exists();

        if ($exists) {
            return $fail('This bank account already exists.');
        }
    }

    /**
     * Prepare the banking attributes to save
     */
    public function bankAttributes()
    {
        $task = Task::find(3);

        if (is_null($task)) {
            abort(409, 'Unable to find task for your account creation action.');
        }

        $bank = Bank::find($this->bank_id);

        return collect($this->safe()->except('bank_id'))->merge([
            'user_id' => $this->user()->id,
            'bank_id' => $bank->id,
            'bank_code' => $bank['bank_code'],
            'bank_name' => $bank['bank_name'],
            'is_primary'  => !$this->user()->banks()->whereIsPrimary(true)->exists(),
            'image' => $bank['bank_logo'],
            'task' => $task->id
        ]);
    }

    /**
     * Handle saving of bank account data
     */
    public function saveBank(Collection $attributes)
    {
        return DB::transaction(function () use ($attributes) {
            Utils::completeTask($this->user()->id, $attributes->get('task'));
            return BankAccount::create($attributes->except('task')->toArray());
        });
    }
}
