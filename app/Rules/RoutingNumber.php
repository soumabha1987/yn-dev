<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class RoutingNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Str::length($value) === 9 && is_numeric($value)) {
            $checkSum = collect(str_split($value))
                ->reduce(function (int $carry, string $char, int $index): int {
                    $multipliers = [3, 7, 1];
                    $carry += (int) $char * $multipliers[$index % 3];

                    return $carry;
                }, 0);

            if ($checkSum === 0 || $checkSum % 10 !== 0) {
                $fail(__('validation.in', ['attribute' => 'routing number']));
            }
        }
    }
}
