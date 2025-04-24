<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $formattedUrl = $this->addSchemeIfMissing($value);

        if (! filter_var($formattedUrl, FILTER_VALIDATE_URL)) {
            $fail(__('validation.url', ['attribute' => $attribute]));

            return;
        }

        if (! $this->hasValidDomain($formattedUrl)) {
            $fail(__('validation.url', ['attribute' => $attribute]));

            return;
        }

        try {
            Http::connectTimeout(15)->head($formattedUrl)->successful();
        } catch (Exception $exception) {
            $fail(__('validation.url', ['attribute' => $attribute]));
        }
    }

    /**
     * Add "http://" if the URL does not have a scheme.
     */
    protected function addSchemeIfMissing(string $value): string
    {
        return Str::startsWith($value, ['http://', 'https://']) ? $value : 'http://' . $value;
    }

    /**
     * Check if the URL has a valid domain.
     */
    protected function hasValidDomain(string $url): bool
    {
        return rescue(fn () => filled($host = parse_url($url, PHP_URL_HOST))
           && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
           && Str::contains($host, '.'), false);
    }
}
