<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\AlphaSingleSpace;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlphaSingleSpaceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideValidAlphaSingleSpace')]
    public function it_passes_alpha_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new AlphaSingleSpace]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidAlphaSingleSpace')]
    public function it_fails_alpha_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => [new AlphaSingleSpace]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidAlphaSingleSpace(): array
    {
        return [
            ['Hello'],
            ['Alphanumeric Text'],
            ['Test Alpha single space'],
        ];
    }

    public static function provideInvalidAlphaSingleSpace(): array
    {
        return [
            ['   HelloWorld'],
            ['Test One  Two'],
            ['Test@One'],
            ['123456788'],
        ];
    }
}
