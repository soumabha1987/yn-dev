<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NamingRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(?=.*[a-zA-Z])[a-zA-Z0-9,\'`.\-]+(?:\s[a-zA-Z0-9,\'`.\-]+)*$/', $value)) {
            $fail(__("The :attribute must contain at least one letter and can only include letters, numbers, spaces, and , ' . -`."));
        }
    }
}
