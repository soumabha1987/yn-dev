<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\AlphaNumberSingleSpace;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlphaNumberSingleSpaceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideValidAlphaNumber')]
    public function it_passes_for_alpha_number_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => new AlphaNumberSingleSpace]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidAlphaNumber')]
    public function it_fails_for_alpha_number_single_space_validation_rule(string $value): void
    {
        $validator = Validator::make(
            ['attribute' => $value],
            ['attribute' => new AlphaNumberSingleSpace]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidAlphaNumber(): array
    {
        return [
            ['Hello test'],
            ['Hello123 World1'],
            ['test23'],
            ['Test 123 Hello'],
        ];
    }

    public static function provideInvalidAlphaNumber(): array
    {
        return [
            ['Hello@test'],
            ['Hello test     '],
            ['Hello123  test7'],
            ['@test123'],
            ['123 456'],
        ];
    }
}
