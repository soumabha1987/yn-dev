<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\SingleSpace;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SingleSpaceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideValidSingleSpace')]
    public function it_passes_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new SingleSpace]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidSingleSpace')]
    public function it_fails_invalid_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new SingleSpace]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidSingleSpace(): array
    {
        return [
            ['Hello'],
            ['Alpha Bravo'],
            ['123 456 789 0'],
        ];
    }

    public static function provideInvalidSingleSpace(): array
    {
        return [
            ['  HelloWorld'],
            ['Test@One      q'],
            ['Hello World  '],
        ];
    }
}
