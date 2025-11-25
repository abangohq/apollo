<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class PinRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null(request()->user()->pin)) {
            $fail('You dont have a transaction pin set.');
        }

        if (!is_null(request()->user()->pin) && !Hash::check($value, request()->user()->pin)) {
            $fail('The selected transaction pin is invalid.');
        }
    }
}
