<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Rules\Recaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    public string $last_name = '';

    public string $dob = '';

    public string $last_four_ssn = '';

    public string $recaptcha = '';

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:50'],
            'dob' => ['required',  'date', 'date_format:Y-m-d', 'before:today'],
            'last_four_ssn' => ['required', 'numeric', 'digits:4'],
            'recaptcha' => ['required', new Recaptcha],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $consumer = Consumer::query()
            ->where('last4ssn', $this->last_four_ssn)
            ->where('dob', $this->dob)
            ->whereRaw("REPLACE(last_name, ' ', '') = ?", [Str::replace(' ', '', $this->last_name)])
            ->first();

        if ($consumer === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.last_name' => __('auth.failed'),
            ]);
        }

        Auth::guard('consumer')->login($consumer);

        RateLimiter::clear($this->throttleKey());

        if (in_array($consumer->status->value, ConsumerStatus::notVerified())) {
            $consumer->update(['status' => ConsumerStatus::JOINED]);
        }

        Session::regenerate();
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
            'form.last_name' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->last_name) . '|' . request()->ip());
    }
}
