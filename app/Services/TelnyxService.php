<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class TelnyxService
{
    public function phoneNumberFormatter(string $phoneNumber): string
    {
        return Str::startsWith($phoneNumber, '+1') ? $phoneNumber : '+1' . ltrim($phoneNumber, '+');
    }
}
