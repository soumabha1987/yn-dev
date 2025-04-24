<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;

enum TemplateType: string
{
    use Values;

    case EMAIL = 'email';
    case SMS = 'sms';
    case E_LETTER = 'e-letter';

    public function displayName(): string
    {
        return match ($this) {
            self::EMAIL => __('Email'),
            self::SMS => __('SMS'),
            self::E_LETTER => __('Eco Letter'),
        };
    }
}
