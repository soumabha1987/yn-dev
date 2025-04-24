<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MultipleEmails implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emails = collect(explode(',', $value))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique();

        if ($emails->contains('') || $emails->isEmpty()) {
            $fail(__('validation.email'));

            return;
        }

        if ($emails->some(fn ($email) => ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $fail(__('validation.email'));

            return;
        }

        if ($emails->count() > 5) {
            $fail(__('validation.max.numeric', ['max' => 5]));
        }
    }
}
