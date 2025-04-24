<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;
use InvalidArgumentException;

enum CreditorCurrentStep: string
{
    use Names;
    use Values;

    case MERCHANTS = 'merchants';
    case PERSONALIZED_LOGO = 'personalized_logo';
    case MASTER_PAY_TERMS = 'pay_terms';
    case TERMS_AND_CONDITIONS = 'terms_and_conditions';
    case ABOUT_US = 'about_us';
    case COMPLETED = 'completed';

    public static function orderedSteps(): array
    {
        return [
            self::MERCHANTS->value,
            self::PERSONALIZED_LOGO->value,
            self::MASTER_PAY_TERMS->value,
            self::TERMS_AND_CONDITIONS->value,
            self::ABOUT_US->value,
            self::COMPLETED->value,
        ];
    }

    public static function stepIsSkipped(string $currentStep, string $step): bool
    {
        return collect(self::stepsToSkipUntil($step))->doesntContain($currentStep);
    }

    private static function stepsToSkipUntil(string $step): array
    {
        $steps = collect(self::values());

        $stepIndex = $steps->search($step, strict: true);

        throw_if($stepIndex === false, InvalidArgumentException::class, "Step $step is not a valid step.");

        return $steps->slice(0, $stepIndex + 1)->all();
    }
}
