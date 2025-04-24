<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\NamingRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NamingRuleTest extends TestCase
{
    #[Test]
    #[DataProvider('provideValidNames')]
    public function it_passes_valid_names_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new NamingRule]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidNames')]
    public function it_fails_invalid_names_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new NamingRule]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidNames(): array
    {
        return [
            ['test Xop'],
            ['test Xop-123'],
            ["X'test 123"],
            ['`test Xop, Dev.`'],
        ];
    }

    public static function provideInvalidNames(): array
    {
        return [
            ['test @Xop'],
            ['12345'],
            [' test Xop '],
            ['test     Xop'],
            ['t        1'],
        ];
    }
}
