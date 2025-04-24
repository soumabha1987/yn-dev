<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum AdminConfigurationSlug: string
{
    use Names;
    use Values;

    case EMAIL_RATE = 'email-rate';

    /**
     * @return array<int, string>
     */
    public function validate(): array
    {
        return match ($this) {
            self::EMAIL_RATE => ['required', 'numeric', 'gte:0', 'regex:/^\d+(\.\d+)?$/'],
        };
    }
}
