<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Rules\AlphaSingleSpace;
use App\Rules\NamingRule;
use BackedEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

enum ConsumerFields: string
{
    use Names;
    use Values;

    case ACCOUNT_NUMBER = 'account_number';
    case MEMBER_ACCOUNT_NUMBER = 'member_account_number';
    case ORIGINAL_ACCOUNT_NAME = 'original_account_name';
    case FIRST_NAME = 'first_name';
    case LAST_NAME = 'last_name';
    case DATE_OF_BIRTH = 'dob';
    case LAST_FOUR_SSN = 'last4ssn';
    case CURRENT_BALANCE = 'current_balance';
    case CONSUMER_EMAIL = 'email1';
    case PHONE = 'mobile1';
    case ADDRESS_LINE_ONE = 'address1';
    case ADDRESS_LINE_TWO = 'address2';
    case CITY = 'city';
    case STATE = 'state';
    case ZIP = 'zip';
    case REFERENCE_NUMBER = 'reference_number';
    case STATEMENT_ID_NUMBER = 'statement_id_number';
    case SUBCLIENT_IDENTIFICATION_NUMBER = 'subclient_id';
    case SUBCLIENT_NAME = 'subclient_name';
    case SUBCLIENT_ACCOUNT_NUMBER = 'subclient_account_number';
    case PLACEMENT_DATE = 'placement_date';
    case EXPIRY_DATE = 'expiry_date';
    case PAY_IN_FULL_DISCOUNT_PERCENTAGE = 'pif_discount_percent';
    case PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE = 'pay_setup_discount_percent';
    case PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE = 'min_monthly_pay_percent';
    case PAYMENT_PLAN_MAX_DAYS_FIRST_PAY = 'max_days_first_pay';
    case MINIMUM_SETTLEMENT_PERCENTAGE = 'minimum_settlement_percentage';
    case MINIMUM_PAYMENT_PLAN_PERCENTAGE = 'minimum_payment_plan_percentage';
    case MAX_FIRST_PAY_DAYS = 'max_first_pay_days';
    case PASSTHROUGH_FIELD_ONE = 'pass_through1';
    case PASSTHROUGH_FIELD_TWO = 'pass_through2';
    case PASSTHROUGH_FIELD_THREE = 'pass_through3';
    case PASSTHROUGH_FIELD_FOUR = 'pass_through4';
    case PASSTHROUGH_FIELD_FIVE = 'pass_through5';

    public function displayName(): string
    {
        $displayNames = [
            self::ACCOUNT_NUMBER->name => 'Original Account Number (Known by Consumer)',
            self::ORIGINAL_ACCOUNT_NAME->name => 'Original Account Name (Known by Consumer)',
            self::FIRST_NAME->name => 'Consumer First Name',
            self::LAST_NAME->name => 'Consumer Last Name',
            self::LAST_FOUR_SSN->name => 'Last Four SSN or Full Social',
            self::DATE_OF_BIRTH->name => 'Date of Birth',
            self::CURRENT_BALANCE->name => 'Beginning Account Balance',
            self::CONSUMER_EMAIL->name => 'Consumer Email',
            self::PHONE->name => 'Consumer Mobile Phone',
            self::MEMBER_ACCOUNT_NUMBER->name => 'Member Account Number',
            self::STATEMENT_ID_NUMBER->name => 'Statement ID Number',
            self::SUBCLIENT_IDENTIFICATION_NUMBER->name => 'Subclient ID Number',
            self::SUBCLIENT_NAME->name => 'Sub Client Name',
            self::SUBCLIENT_ACCOUNT_NUMBER->name => 'Sub Client Account Number',
            self::REFERENCE_NUMBER->name => 'Reference Number',
            self::PLACEMENT_DATE->name => 'Placement Date',
            self::EXPIRY_DATE->name => 'Expiry Date',
            self::ADDRESS_LINE_ONE->name => 'Address Line 1',
            self::ADDRESS_LINE_TWO->name => 'Address Line 2',
            self::CITY->name => 'City',
            self::STATE->name => 'State',
            self::ZIP->name => 'Zip',
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE->name => 'Pay In Full Discount % (override Master/Sub Pay Term Offers)',
            self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->name => 'Payment Plan - Bal Discount % (override Master/Sub Pay Term Offers)',
            self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->name => 'Payment Plan/Min. Monthly Payment % of Bal (override Master/Sub Pay Term Offers)',
            self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->name => 'Payment Plan/Max Days First Pay (override Master/Sub Pay Term Offers)',
            self::MINIMUM_SETTLEMENT_PERCENTAGE->name => 'Minimum Settlement Percentage (override Master/Sub Pay Term Offers)',
            self::MINIMUM_PAYMENT_PLAN_PERCENTAGE->name => 'Minimum Payment Plan (override Master/Sub Pay Term Offers)',
            self::MAX_FIRST_PAY_DAYS->name => 'Max First Payment Date (override Master/Sub Pay Term Offers)',
            self::PASSTHROUGH_FIELD_ONE->name => 'Passthrough Field 1',
            self::PASSTHROUGH_FIELD_TWO->name => 'Passthrough Field 2',
            self::PASSTHROUGH_FIELD_THREE->name => 'Passthrough Field 3',
            self::PASSTHROUGH_FIELD_FOUR->name => 'Passthrough Field 4',
            self::PASSTHROUGH_FIELD_FIVE->name => 'Passthrough Field 5',
        ];

        return $displayNames[$this->name];
    }

    /**
     * @return array<string, BackedEnum>
     */
    public static function displaySelectionBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case): array => [$case->displayName() => $case])
            ->toArray();
    }

    /**
     * @return array<int, mixed>
     */
    public function validate(?int $companyId = null): array
    {
        return match ($this) {
            self::ACCOUNT_NUMBER => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique(Consumer::class, 'account_number')
                    ->whereNotIn('status', [ConsumerStatus::DEACTIVATED, ConsumerStatus::DISPUTE, ConsumerStatus::NOT_PAYING]),
            ],
            self::ORIGINAL_ACCOUNT_NAME => ['required', 'string', 'min:2', 'max:50'],
            self::FIRST_NAME => ['required', 'string', 'min:2', 'max:25', new NamingRule],
            self::LAST_NAME => ['required', 'string', 'min:2', 'max:30', new NamingRule],
            self::DATE_OF_BIRTH => ['required', 'date', 'before:today'],// 16 year minimum rule
            self::LAST_FOUR_SSN => ['required', 'numeric', 'digits_between:4,9'],
            self::CURRENT_BALANCE => ['required', 'numeric', 'gt:0', 'decimal:2'],
            self::REFERENCE_NUMBER => ['nullable', 'string', 'max:30'],
            self::STATEMENT_ID_NUMBER => ['nullable', 'string', 'max:30'],
            self::PLACEMENT_DATE => ['nullable', 'date'],
            self::EXPIRY_DATE => ['nullable', 'date'],
            self::ADDRESS_LINE_ONE => ['required', 'string', 'min:2', 'max:100'],
            self::ADDRESS_LINE_TWO => ['nullable', 'string', 'min:2', 'max:100'],
            self::CITY => ['required', 'string', 'min:2', 'max:30', new AlphaSingleSpace],
            self::STATE => ['required', 'string', 'max:3', Rule::in(State::values())],
            self::ZIP => ['required', 'regex:/^\d{5}(-\d{4})?$/', 'max:10'],
            self::MEMBER_ACCOUNT_NUMBER => ['required', 'string', 'min:2', 'max:50'],
            self::PHONE => ['required', 'phone:US'],
            self::CONSUMER_EMAIL => ['required', 'string', 'email:rfc,dns'],
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE => ['nullable', 'integer', 'min:0', 'max:99'],
            self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE => ['nullable', 'integer', 'min:0', 'max:99'],
            self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE => ['nullable', 'integer', 'min:1', 'max:99'],
            self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY => ['nullable', 'integer', 'min:1', 'max:1000'],
            self::MINIMUM_SETTLEMENT_PERCENTAGE => ['nullable', 'integer', 'min:1', 'max:99'],
            self::MINIMUM_PAYMENT_PLAN_PERCENTAGE => ['nullable', 'integer', 'min:1', 'max:99'],
            self::MAX_FIRST_PAY_DAYS => ['nullable', 'integer', 'min:1', 'max:1000'],
            self::SUBCLIENT_IDENTIFICATION_NUMBER => [
                'nullable',
                'string',
                Rule::exists(Subclient::class, 'unique_identification_number')
                    ->when($companyId, function (Exists $query) use ($companyId): void {
                        $query->where('company_id', $companyId);
                    }),
            ],
            self::SUBCLIENT_NAME => ['nullable', 'string', new NamingRule],
            self::SUBCLIENT_ACCOUNT_NUMBER => ['nullable', 'string'],
            self::PASSTHROUGH_FIELD_ONE => ['nullable', 'string', 'max:100'],
            self::PASSTHROUGH_FIELD_TWO => ['nullable', 'string', 'max:100'],
            self::PASSTHROUGH_FIELD_THREE => ['nullable', 'string', 'max:100'],
            self::PASSTHROUGH_FIELD_FOUR => ['nullable', 'string', 'max:100'],
            self::PASSTHROUGH_FIELD_FIVE => ['nullable', 'string', 'max:100'],
        };
    }

    public function customMessage(): array
    {
        return match ($this) {
            self::ACCOUNT_NUMBER => [
                self::ACCOUNT_NUMBER->value . '.required' => __('The OrgAccount# is required.'),
                self::ACCOUNT_NUMBER->value . '.unique' => __('OrgAccount# is active in another YN member account.'),
                self::ACCOUNT_NUMBER->value . '.min' => __('OrigAcct# must be at least 2 characters.'),
                self::ACCOUNT_NUMBER->value . '.max' => __('OrigAcct# must not exceed 50 characters.'),
            ],
            self::ORIGINAL_ACCOUNT_NAME => [
                self::ORIGINAL_ACCOUNT_NAME->value . '.required' => __('The MemberAcct# is required.'),
                self::ORIGINAL_ACCOUNT_NAME->value . '.string' => __('MemberAcct# is not a string.'),
                self::ORIGINAL_ACCOUNT_NAME->value . '.min' => __('MemberAcct# must be at least 2 characters.'),
                self::ORIGINAL_ACCOUNT_NAME->value . '.max' => __('MemberAcct# must not exceed 50 characters.'),
            ],
            self::FIRST_NAME => [
                self::FIRST_NAME->value . '.required' => __('The FirstName is required.'),
                self::FIRST_NAME->value . '.string' => __('FirstName is not a string.'),
                self::FIRST_NAME->value . '.min' => __('FirstName must be at least 2 characters.'),
                self::FIRST_NAME->value . '.max' => __('FirstName must not exceed 25 characters.'),
            ],
            self::LAST_NAME => [
                self::LAST_NAME->value . '.required' => __('The LastName is required.'),
                self::LAST_NAME->value . '.string' => __('LastName must be a string.'),
                self::LAST_NAME->value . '.min' => __('LastName less than 2 characters.'),
                self::LAST_NAME->value . '.max' => __('LastName must not exceed 30 characters.'),
            ],
            self::DATE_OF_BIRTH => [
                self::DATE_OF_BIRTH->value . '.required' => __('The DOB is required. '),
                self::DATE_OF_BIRTH->value . '.date' => __('The DOB format does not match the expected profile.'),
            ],
            self::LAST_FOUR_SSN => [
                self::LAST_FOUR_SSN->value . '.required' => __('The SSN is required.'),
                self::LAST_FOUR_SSN->value . '.digits_between' => __('SSN must be either 4 or 9 digits.'),
            ],
            self::CURRENT_BALANCE => [
                self::CURRENT_BALANCE->value . '.required' => __('The CurrentBalance is required.'),
                self::CURRENT_BALANCE->value . '.numeric' => __('CurrentBalance must be a number.'),
                self::CURRENT_BALANCE->value . '.gt' => __('CurrentBalance must be greater than $0.00.'),
                self::CURRENT_BALANCE->value . '.decimal' => __('CurrentBalance contains invalid characters.'),
            ],
            self::REFERENCE_NUMBER => [
                self::REFERENCE_NUMBER->value . '.string' => __('ReferenceNumber must be a string.'),
                self::REFERENCE_NUMBER->value . '.min' => __('ReferenceNumber must have at least 1 character.'),
                self::REFERENCE_NUMBER->value . '.max' => __('ReferenceNumber must not exceed 100 characters.'),
            ],
            self::STATEMENT_ID_NUMBER => [
                self::STATEMENT_ID_NUMBER->value . '.string' => __('StatementID# must be a string.'),
                self::STATEMENT_ID_NUMBER->value . '.min' => __('StatementID# must have at least 1 character.'),
                self::STATEMENT_ID_NUMBER->value . '.max' => __('StatementID# must not exceed 100 characters.'),
            ],
            self::PLACEMENT_DATE => [
                self::PLACEMENT_DATE->value . '.date' => __('PlacementDate format does not match the required header profile.'),
            ],
            self::EXPIRY_DATE => [
                self::EXPIRY_DATE->value . '.date' => __('ExpiryDate format does not match the required header profile.'),
            ],
            self::ADDRESS_LINE_ONE => [
                self::ADDRESS_LINE_ONE->value . '.required' => __('The Address1  is required.'),
                self::ADDRESS_LINE_ONE->value . '.string' => __('Address1 must be a string.'),
                self::ADDRESS_LINE_ONE->value . '.min' => __('Address1 must have at least 2 characters.'),
                self::ADDRESS_LINE_ONE->value . '.max' => __('Address1 must not exceed 100 characters.'),
            ],
            self::ADDRESS_LINE_TWO => [
                self::ADDRESS_LINE_TWO->value . '.required' => __('The Address2  is required.'),
                self::ADDRESS_LINE_TWO->value . '.string' => __('Address2 must be a string.'),
                self::ADDRESS_LINE_TWO->value . '.min' => __('Address2 must have at least 2 characters.'),
                self::ADDRESS_LINE_TWO->value . '.max' => __('Address2 must not exceed 100 characters.'),
            ],
            self::CITY => [
                self::CITY->value . '.required' => __('The City  is required.'),
                self::CITY->value . '.string' => __('City must be a string.'),
                self::CITY->value . '.min' => __('City must have at least 2 characters.'),
                self::CITY->value . '.max' => __('City must not exceed 30 characters.'),
            ],
            self::STATE => [
                self::STATE->value . '.required' => __('The State  is required.'),
                self::STATE->value . '.string' => __('State must be a string.'),
                self::STATE->value . '.max' => __('State must not exceed 3 letters.'),
            ],
            self::ZIP => [
                self::ZIP->value . '.required' => __('The ZipCode  is required.'),
                self::ZIP->value . '.regex' => __('ZipCode is not a valid US ZIP code.'),
            ],
            self::MEMBER_ACCOUNT_NUMBER => [
                self::MEMBER_ACCOUNT_NUMBER->value . '.required' => __('The MemberAcct#  is required.'),
                self::MEMBER_ACCOUNT_NUMBER->value . '.string' => __('MemberAcct# must be a string.'),
                self::MEMBER_ACCOUNT_NUMBER->value . '.min' => __('MemberAcct# must have at least 2 characters.'),
                self::MEMBER_ACCOUNT_NUMBER->value . '.max' => __('MemberAcct# must not exceed 50 characters.'),
            ],
            self::PHONE => [
                self::PHONE->value . '.required' => __('The mobile number field is required.'),
                self::PHONE->value . '.phone' => __('The mobile number is invalid.'),
            ],
            self::CONSUMER_EMAIL => [
                self::CONSUMER_EMAIL->value . '.required' => __('The email field is required.'),
                self::CONSUMER_EMAIL->value . '.string' => __('Email must be a string.'),
                self::CONSUMER_EMAIL->value . '.email' => __('Email address is invalid.'),
            ],
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE => [
                self::PAY_IN_FULL_DISCOUNT_PERCENTAGE->value . '.min' => __('PIF Balance Discount Percentage must have at least 1 character.'),
                self::PAY_IN_FULL_DISCOUNT_PERCENTAGE->value . '.max' => __('PIF Balance Discount Percentage must be a whole number between 0 and 99.'),
            ],
            self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE => [
                self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->value . '.min' => __('Payment Plan Balance Discount Percentage must have at least 1 character.'),
                self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->value . '.max' => __('Payment Plan Balance Discount Percentage must be a whole number between 0 and 99.'),
            ],
            self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE => [
                self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->value . '.min' => __('Minimum Monthly Payment Percentage must have at least 1 character.'),
                self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->value . '.max' => __('Minimum Monthly Payment Percentage must be a whole number between 1 and 99.'),
            ],
            self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY => [
                self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->value . '.min' => __('Max Days for 1st Payment cannot be less than 1.'),
                self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->value . '.max' => __('Max Days for 1st Payment cannot exceed 1,000 days.'),
            ],
            self::SUBCLIENT_IDENTIFICATION_NUMBER => [
                self::SUBCLIENT_IDENTIFICATION_NUMBER->value . '.string' => __('SubClient ID must be a string.'),
            ],
            self::SUBCLIENT_NAME => [
                self::SUBCLIENT_NAME->value . '.string' => __('SubClient Name must be a string.'),
            ],
            self::SUBCLIENT_ACCOUNT_NUMBER => [
                self::SUBCLIENT_ACCOUNT_NUMBER->value . '.string' => __('SubClient Account Number must be a string.'),
            ],
            self::PASSTHROUGH_FIELD_ONE => [
                self::PASSTHROUGH_FIELD_ONE->value . '.string' => __('Passthrough Field 1 must be a string.'),
                self::PASSTHROUGH_FIELD_ONE->value . '.max' => __('Passthrough Field 1 cannot exceed 150 characters.'),
            ],
            self::PASSTHROUGH_FIELD_TWO => [
                self::PASSTHROUGH_FIELD_TWO->value . '.string' => __('Passthrough Field 2 must be a string.'),
                self::PASSTHROUGH_FIELD_TWO->value . '.max' => __('Passthrough Field 2 cannot exceed 150 characters.'),
            ],
            self::PASSTHROUGH_FIELD_THREE => [
                self::PASSTHROUGH_FIELD_THREE->value . '.string' => __('Passthrough Field 3 must be a string.'),
                self::PASSTHROUGH_FIELD_THREE->value . '.max' => __('Passthrough Field 3 cannot exceed 150 characters.'),
            ],
            self::PASSTHROUGH_FIELD_FOUR => [
                self::PASSTHROUGH_FIELD_FOUR->value . '.string' => __('Passthrough Field 4 must be a string.'),
                self::PASSTHROUGH_FIELD_FOUR->value . '.max' => __('Passthrough Field 4 cannot exceed 150 characters.'),
            ],
            self::PASSTHROUGH_FIELD_FIVE => [
                self::PASSTHROUGH_FIELD_FIVE->value . '.string' => __('Passthrough Field 5 must be a string.'),
                self::PASSTHROUGH_FIELD_FIVE->value . '.max' => __('Passthrough Field 5 cannot exceed 150 characters.'),
            ],
            default => [],
        };
    }

    public function updateValidate(bool $isPpaBalanceDiscountPercentage, bool $isMinMonthlyPercentage): array
    {
        return match ($this) {
            self::PHONE => ['nullable', 'phone:US'],
            self::CONSUMER_EMAIL => ['nullable', 'string', 'email:rfc,dns'],
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE => ['nullable', 'integer', 'min:0', 'max:99'],
            self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE => ['nullable', Rule::requiredIf($isMinMonthlyPercentage), 'integer', 'min:0', 'max:99'],
            self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE => ['nullable', Rule::requiredIf($isPpaBalanceDiscountPercentage), 'integer', 'min:1', 'max:99'],
            self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY => ['nullable', 'integer', 'min:1', 'max:1000'],
            self::MINIMUM_SETTLEMENT_PERCENTAGE => ['nullable', 'integer', 'min:1', 'max:99'],
            self::MINIMUM_PAYMENT_PLAN_PERCENTAGE => ['nullable', 'integer', 'min:1', 'max:99'],
            self::MAX_FIRST_PAY_DAYS => ['nullable', 'integer', 'min:1', 'max:1000'],
            default => [],
        };
    }

    /**
     * @return array<string>
     */
    public static function getRequiredFields(): array
    {
        return collect(self::cases())
            ->flatMap(fn ($case) => collect($case->validate())->contains('required') ? [$case->value] : [])
            ->values()
            ->toArray();
    }

    /**
     * @return array<string>
     */
    public static function getDecimalFields(): array
    {
        return collect(self::cases())
            ->flatMap(fn ($case) => collect($case->validate())->contains('decimal:2') ? [$case->value] : [])
            ->values()
            ->toArray();
    }

    public static function fromDisplayName(string $displayName): ?self
    {
        return collect(self::cases())
            ->first(fn ($case) => $case->displayName() === $displayName);
    }

    /**
     * Gets the Enum by value, if it exists.
     */
    public static function tryFromValue(string $value): ?static
    {
        $cases = array_filter(self::cases(), fn ($case): bool => $case->value === $value);

        return array_values($cases)[0] ?? null;
    }

    /**
     * @return array<string>
     */
    public static function getUpdateFields(): array
    {
        return [
            self::ACCOUNT_NUMBER->displayName(),
            self::PHONE->displayName(),
            self::CONSUMER_EMAIL->displayName(),
            self::PAY_IN_FULL_DISCOUNT_PERCENTAGE->displayName(),
            self::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName(),
            self::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->displayName(),
            self::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->displayName(),
            self::MINIMUM_SETTLEMENT_PERCENTAGE->displayName(),
            self::MINIMUM_PAYMENT_PLAN_PERCENTAGE->displayName(),
            self::MAX_FIRST_PAY_DAYS->displayName(),
        ];
    }
}
