<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Rules\Recaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class VerifySsnForm extends Form
{
    public string $last_four_ssn = '';

    public string $recaptcha = '';

    /**
     * @return array<string, array<int, string | Recaptcha>>
     */
    public function rules(): array
    {
        return [
            'last_four_ssn' => ['required', 'integer', 'digits:4'],
            'recaptcha' => ['required', new Recaptcha],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'recaptcha' => 'Google reCAPTCHA',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(Consumer $consumer): bool
    {
        $this->ensureIsNotRateLimited();

        $data = [
            'last_name' => $consumer->last_name,
            'dob' => optional($consumer->dob)->toDateString(),
            'last4ssn' => $this->last_four_ssn,
        ];

        $consumer = Consumer::query()->where($data)->first();

        if ($consumer === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.last_four_ssn' => __('Sorry! The Last 4 SSN you entered did not match with our records. You can try again or reach out to your Creditor!'),
            ]);
        }

        Session::put('required_ssn_verification', false);

        if (in_array($consumer->status->value, ConsumerStatus::notVerified())) {
            $consumer->update(['status' => ConsumerStatus::JOINED]);
        }

        return true;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.ssn' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->last_four_ssn) . '|' . request()->ip());
    }
}
