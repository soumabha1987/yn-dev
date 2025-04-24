<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\MultipleEmails;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultipleEmailsTest extends TestCase
{
    #[Test]
    #[DataProvider('validEmailsProvider')]
    public function it_passes_valid_email_cases(string $emails): void
    {
        $validator = Validator::make(
            ['emails' => $emails],
            ['emails' => [new MultipleEmails]]
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('invalidEmailsProvider')]
    public function it_fails_invalid_email_cases(string $emails): void
    {
        $validator = Validator::make(
            ['emails' => $emails],
            ['emails' => [new MultipleEmails]]
        );

        $this->assertTrue($validator->fails());
    }

    public static function validEmailsProvider(): array
    {
        return [
            ['test@test.com, testxop@test.com, test@doe.com'],
            ['email1@test.com, email2@test.com, email3@test.com, email4@test.com, email5@test.com'],
            ['one@test.com'],
            ['email1@test.com, email2@test.com, email3@test.com, email4@test.com, email5@test.com, email1@test.com, email3@test.com'],
        ];
    }

    public static function invalidEmailsProvider(): array
    {
        return [
            ['test@test.com, invalid_email, test@.com'],
            ['test@@test.com, test@test.com'],
            ['test.com, testxop@test.com'],
            ["''"],
            ['email1@test.com, email2@test.com, email3@test.com, email4@test.com, email5@test.com, email6@test.com'],
        ];
    }
}
