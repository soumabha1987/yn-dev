<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AddressSingleSpace implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (preg_match('/[A-Za-z]/', $value) && preg_match('/^(?!.* {2,}).*$/', $value))) {
            $fail('The :attribute field must contain at least one alphabetic character and no multiple consecutive spaces.');
        }
    }
}
