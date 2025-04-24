<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\ValidUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(fn () => Http::response());
    }

    #[Test]
    #[DataProvider('provideValidUrls')]
    public function it_passes_valid_url_validation_rule(string $url): void
    {
        $validator = Validator::make(
            ['url' => $url],
            ['url' => new ValidUrl]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('provideInvalidUrls')]
    public function it_fails_invalid_url_validation_rule(string $url): void
    {
        $validator = Validator::make(
            ['url' => $url],
            ['url' => [new ValidUrl]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function provideValidUrls(): array
    {
        return [
            ['http://example.com'],
            ['http://example.com/about'],
            ['https://example.org/home'],
            ['https://sub.example.net/projects'],
            ['https://my-site.dev/contact'],
            ['https://www.valid-site.com'],
            ['validsite.io'],
        ];
    }

    public static function provideInvalidUrls(): array
    {
        return [
            ['.invalid'],
            ['htp://invalid.com'],
            ['https://'],
            ['http://localhost:3000'],
            ['http://incomplete-url'],
        ];
    }
}
