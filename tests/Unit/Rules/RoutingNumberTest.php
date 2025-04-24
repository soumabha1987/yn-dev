<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\RoutingNumber;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutingNumberTest extends TestCase
{
    #[Test]
    #[DataProvider('validRoutingNumbersProvider')]
    public function it_can_validate_routing_number(string $value): void
    {
        $validator = Validator::make(
            ['routing_number' => $value],
            ['routing_number' => [new RoutingNumber]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('invalidRoutingNumbersProvider')]
    public function it_fails_invalid_routing_number(string $value): void
    {
        $validator = Validator::make(
            ['routing_number' => $value],
            ['routing_number' => [new RoutingNumber]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function validRoutingNumbersProvider(): array
    {
        return [
            ['021000021'],
            ['121042882'],
            ['011103093'],
            ['011000138'],
            ['021101108'],
        ];
    }

    public static function invalidRoutingNumbersProvider(): array
    {
        return [
            ['123456788'],
            ['111111111'],
            ['222222222'],
            ['000000001'],
            ['876543219'],
        ];
    }
}
