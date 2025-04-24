<?php

declare(strict_types=1);

namespace App\Enums\Traits;

use Illuminate\Support\Str;

trait Names
{
    public function displayName(): string
    {
        return Str::of($this->name)->title()->headline()->toString();
    }
}
