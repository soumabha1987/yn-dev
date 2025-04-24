<?php

declare(strict_types=1);

namespace App\Enums\Traits;

trait SelectionBox
{
    use Names;

    public static function displaySelectionBox(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case): array => [
            $case->value => $case->displayName(),
        ])->toArray();
    }
}
