<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Values;
use App\Models\Consumer;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

enum TemplateCustomField: string
{
    use Values;

    case ACCOUNT_NUMBER = '[Original Account Number]';
    case MEMBER_ACCOUNT_NUMBER = '[Member Account Number]';
    case ORIGINAL_ACCOUNT_NAME = '[Original Name]';
    case FIRST_NAME = '[First Name]';
    case LAST_NAME = '[Last Name]';
    case DOB = '[Birth Date]';
    case LAST_4_SSN = '[Last 4 SSN]';
    case ORIGINAL_BALANCE = '[Original Balance]';
    case CURRENT_BALANCE = '[Current Balance]';
    case CONSUMER_EMAIL = '[Consumer Email]';
    case CONSUMER_PHONE = '[Consumer Phone]';
    case ADDRESS_FIRST_LINE = '[Address 1]';
    case ADDRESS_SECOND_LINE = '[Address 2]';
    case CITY = '[City]';
    case STATE = '[State]';
    case ZIP_CODE = '[Zip Code]';
    case REFERENCE_NUMBER = '[Ref Number]';
    case STATEMENT_NUMBER = '[Statement Number]';
    case SUB_IDENTIFICATION = '[Sub Identification(ID)]';
    case SUBCLIENT_NAME = '[Sub Name]';
    case SUBCLIENT_ACCOUNT_NUMBER = '[Sub Number]';
    case PLACEMENT = '[Placement]';
    case EXPIRY_DATE = '[Expiration date]';
    case PAY_IN_FULL_DISCOUNT_PERCENTAGE = '[Pay Full %]';
    case PAYMENT_SETUP_DISCOUNT_PERCENTAGE = '[Payment-Discount %]';
    case PAYMENT_SETUP_DISCOUNT_AMOUNT = '[Payment Monthly % Balance]';
    case PAYMENT_DAYS_PAY = '[Payment Days Pay]';
    case YOU_NEGOTIATE_LINK = '[You Negotiate Link]';
    case PASS_THROUGH_1 = '[Pass Through 1]';
    case PASS_THROUGH_2 = '[Pass Through 2]';
    case PASS_THROUGH_3 = '[Pass Through 3]';
    case PASS_THROUGH_4 = '[Pass Through 4]';
    case PASS_THROUGH_5 = '[Pass Through 5]';
    case MEMBER_INDUSTRY_TYPE = '[Member Industry Type]';
    case NEGOTIATED_EXPIRY_DATE = '[Negotiated Expiry Date]';

    public static function swapContent(Consumer $consumer, string $content): string
    {
        $replacements = collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->mapToConsumerValue($consumer)])
            ->toArray();

        return Str::of($content)->swap($replacements)->value();
    }

    private function mapToConsumerValue(Consumer $consumer): mixed
    {
        $consumer->loadMissing(['company', 'subclient', 'consumerNegotiation']);

        return match ($this) {
            self::ACCOUNT_NUMBER => $consumer->account_number,
            self::MEMBER_ACCOUNT_NUMBER => $consumer->member_account_number,
            self::ORIGINAL_ACCOUNT_NAME => $consumer->original_account_name,
            self::FIRST_NAME => $consumer->first_name,
            self::LAST_NAME => $consumer->last_name,
            self::DOB => $consumer->dob->format('M d, Y'),
            self::LAST_4_SSN => $consumer->last4ssn,
            self::ORIGINAL_BALANCE => Number::currency((float) $consumer->total_balance),
            self::CURRENT_BALANCE => Number::currency((float) $consumer->current_balance),
            self::CONSUMER_EMAIL => $consumer->email1,
            self::CONSUMER_PHONE => $consumer->mobile1,
            self::ADDRESS_FIRST_LINE => $consumer->address1,
            self::ADDRESS_SECOND_LINE => $consumer->address2,
            self::CITY => $consumer->city,
            self::STATE => State::tryFrom($consumer->state)->displayName(),
            self::ZIP_CODE => $consumer->zip,
            self::REFERENCE_NUMBER => $consumer->reference_number,
            self::STATEMENT_NUMBER => $consumer->statement_id_number,
            self::SUB_IDENTIFICATION => $consumer->subclient->unique_identification_number ?? 'N/A',
            self::SUBCLIENT_NAME => $consumer->subclient->subclient_name ?? 'N/A',
            self::SUBCLIENT_ACCOUNT_NUMBER => $consumer->subclient ? '#' . $consumer->subclient->bank_account_number : 'N/A',
            self::PLACEMENT => $consumer->placement_date?->format('M d, Y') ?? 'N/A',
            self::EXPIRY_DATE => $consumer->expiry_date ? $consumer->expiry_date->format('M d, Y') : $consumer->created_at->addDays(30)->format('M d, Y'),
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE => Number::percentage($this->getPIFPercentage($consumer)),
            self::PAYMENT_SETUP_DISCOUNT_PERCENTAGE => Number::percentage($this->getPaySetupDiscountPercentage($consumer)),
            self::PAYMENT_SETUP_DISCOUNT_AMOUNT => Number::currency($this->getMinimumMonthlyPayAmount($consumer)),
            self::PAYMENT_DAYS_PAY => $this->getPaymentPayDays($consumer),
            self::YOU_NEGOTIATE_LINK => $consumer->invitation_link,
            self::PASS_THROUGH_1 => $consumer->pass_through1,
            self::PASS_THROUGH_2 => $consumer->pass_through2,
            self::PASS_THROUGH_3 => $consumer->pass_through3,
            self::PASS_THROUGH_4 => $consumer->pass_through4,
            self::PASS_THROUGH_5 => $consumer->pass_through5,
            self::MEMBER_INDUSTRY_TYPE => $consumer->company->business_category,
            self::NEGOTIATED_EXPIRY_DATE => $consumer->payment_setup || ! $consumer->offer_accepted
                ? 'N/A'
                : $this->negotiatedExpiryDate($consumer),
        };
    }

    private function getPaySetupDiscountPercentage(Consumer $consumer): float
    {
        return $consumer->pay_setup_discount_percent
            ?? $consumer->subclient->ppa_balance_discount_percent
            ?? $consumer->company->ppa_balance_discount_percent;
    }

    private function getPIFPercentage(Consumer $consumer): float
    {
        return $consumer->pif_discount_percent
            ?? $consumer->subclient->pif_balance_discount_percent
            ?? $consumer->company->pif_balance_discount_percent;
    }

    private function getPaySetupDiscountPercentageAmount(Consumer $consumer): float
    {
        return $consumer->current_balance - ($consumer->current_balance * $this->getPaySetupDiscountPercentage($consumer) / 100);
    }

    private function getMinimumMonthlyPercentage(Consumer $consumer): ?float
    {
        return $consumer->min_monthly_pay_percent
            ?? $consumer->subclient->min_monthly_pay_percent
            ?? $consumer->company->min_monthly_pay_percent;
    }

    private function getMinimumMonthlyPayAmount(Consumer $consumer): float
    {
        return $this->getPaySetupDiscountPercentageAmount($consumer) * $this->getMinimumMonthlyPercentage($consumer) / 100;
    }

    private function getPaymentPayDays(Consumer $consumer): ?int
    {
        return $consumer->max_days_first_pay
            ?? $consumer->subclient->max_days_first_pay
            ?? $consumer->company->max_days_first_pay;
    }

    private function negotiatedExpiryDate(Consumer $consumer): string
    {
        if (blank($consumer->consumerNegotiation)) {
            return 'N/A';
        }

        return $consumer->consumerNegotiation->counter_offer_accepted
            ? $consumer->consumerNegotiation->counter_first_pay_date->format('M d, Y')
            : $consumer->consumerNegotiation->first_pay_date->format('M d, Y');
    }
}
