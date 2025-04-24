<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class AgeBetween18And100Rule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $age = Carbon::parse($value);

        if ($age->greaterThan(now()->subYears(18))) {
            $fail(__('You must be at least 18 years old to proceed.'));
        }

        if ($age->lessThanOrEqualTo(now()->subYears(100))) {
            $fail(__('You must not be older than 100 years old to proceed.'));
        }
    }
}
