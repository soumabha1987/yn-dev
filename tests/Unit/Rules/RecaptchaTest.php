<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    #[Test]
    public function it_passes_valid_recaptcha_validation_rule(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $validator = Validator::make(
            ['recaptcha' => 'valid_recaptcha_response'],
            ['recaptcha' => ['required', new Recaptcha]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_fails_invalid_recaptch_validation_rule(): void
    {
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $validator = Validator::make(
            ['recaptcha' => 'invalid_recaptcha_response'],
            ['recaptcha' => [new Recaptcha]]
        );

        $this->assertTrue($validator->fails());
    }
}
