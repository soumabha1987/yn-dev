<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SingleSpace implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(?!.* {2})[a-zA-Z0-9,!@#\$%\^\&*\)\(+=._-]+( [a-zA-Z0-9,!@#\$%\^\&*\)\(+=._-]+)*$/', $value)) {
            $fail(__('The :attribute field should only contain single space between words.'));
        }
    }
}
