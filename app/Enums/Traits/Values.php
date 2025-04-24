<?php

declare(strict_types=1);

namespace App\Enums\Traits;

trait Values
{
    /**
     * @return array<int, string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
