<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AlphaNumberSingleSpace implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(?=.*[A-Za-z])[A-Za-z0-9]+( [A-Za-z0-9]+)*$/', $value)) {
            $fail('The :attribute field should only contain alphabetic characters, number and a single space between words.');
        }
    }
}
