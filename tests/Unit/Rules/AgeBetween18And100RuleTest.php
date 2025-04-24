<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\AgeBetween18And100Rule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgeBetween18And100RuleTest extends TestCase
{
    #[Test]
    public function it_can_validate_below_18_year_age_is_not_allowed(): void
    {
        $validator = Validator::make(
            ['birth_date' => now()->addYear()->toDateString()],
            ['birth_date' => [new AgeBetween18And100Rule]]
        );

        $this->assertFalse($validator->passes());
    }

    #[Test]
    public function it_fails_for_age_above_100_years_validation_rule(): void
    {
        $validator = Validator::make(
            ['birth_date' => now()->subYears(101)->toDateString()],
            ['birth_date' => [new AgeBetween18And100Rule]]
        );

        $this->assertFalse($validator->passes());
    }

    #[Test]
    public function it_passes_for_age_between_18_and_100_years_validation_rule(): void
    {
        $validator = Validator::make(
            ['birth_date' => now()->subYears(25)->toDateString()],
            ['birth_date' => [new AgeBetween18And100Rule]]
        );

        $this->assertTrue($validator->passes());
    }
}
