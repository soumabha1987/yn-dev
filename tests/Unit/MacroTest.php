<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Timezone;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MacroTest extends TestCase
{
    #[Test]
    #[DataProvider('dates')]
    public function format_with_timezone_macro(
        string $utcDateTime,
        string $formattedDateTime,
        ?string $timezone = null,
        string $dateFormat = 'M d, Y'
    ): void {
        $formatDate = Carbon::createFromFormat('Y-m-d H:i:s', $utcDateTime)
            ->formatWithTimezone($timezone, $dateFormat);

        $this->assertEquals($formatDate, $formattedDateTime);
    }

    public static function dates(): array
    {
        return [
            ['2025-01-01 04:00:00', 'Dec 31, 2024'],
            ['2025-01-01 10:00:00', 'Jan 01, 2025'],
            ['2025-01-22 04:00:00', 'Jan 21, 2025'],
            ['2025-01-22 04:00:00', 'Jan 21, 2025 23:00', null, 'M d, Y H:i'],
            ['2025-01-22 05:30:00', 'Jan 21, 2025', Timezone::CST->value],
            ['2025-01-22 06:30:00', 'Jan 22, 2025', Timezone::CST->value],
            ['2025-01-22 06:30:00', 'Jan 22, 2025 00:30', Timezone::CST->value, 'M d, Y H:i'],
        ];
    }
}
