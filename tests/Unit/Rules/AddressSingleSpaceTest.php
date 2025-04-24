<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\AddressSingleSpace;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddressSingleSpaceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideValidAddresses')]
    public function it_passes_for_address_single_space_validation_rule(string $address): void
    {
        $validator = Validator::make(
            ['address' => $address],
            ['address' => [new AddressSingleSpace]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidAddresses')]
    public function it_fails_address_single_space_validation_rule(string $address): void
    {
        $validator = Validator::make(
            ['address' => $address],
            ['address' => [new AddressSingleSpace]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidAddresses(): array
    {
        return [
            ['123 Main Street'],
            ['Apt 4B, 456 Elm St.'],
            ['Office 1A, 789 Park Ave'],
            ['12345 Broadway Blvd.'],
            ['P.O. Box 678'],
        ];
    }

    public static function provideInvalidAddresses(): array
    {
        return [
            ['test     test'],
            ['test       '],
            ['          test'],
            ['1234567890'],
        ];
    }
}
