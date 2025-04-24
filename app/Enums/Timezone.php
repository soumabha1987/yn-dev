<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;

enum Timezone: string
{
    use Names;
    use Values;

    case CST = 'CST';
    case EST = 'EST';
    case MST = 'MST';
    case PST = 'PST';

    public function getName(): string
    {
        return match ($this) {
            self::CST => 'Central Standard Time',
            self::EST => 'Eastern Standard Time',
            self::MST => 'Mountain Standard Time',
            self::PST => 'Pacific Standard Time',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function displaySelectionBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getName()])
            ->sort()
            ->all();
    }
}
