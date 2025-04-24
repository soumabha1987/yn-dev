<?php

declare(strict_types=1);

namespace Tests\Feature;

use AllowDynamicProperties;
use App\Enums\BankAccountType;
use App\Enums\CompanyCategory;
use App\Enums\IndustryType;
use App\Enums\MerchantName;
use App\Enums\State;
use App\Enums\YearlyVolumeRange;
use App\Models\Merchant;
use App\Models\User;
use App\Services\MerchantService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class MerchantServiceVerifyTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_store_and_verify_true_tilled_merchant(): void
    {
        $this->user->company->update([
            'tilled_merchant_account_id' => null,
            'tilled_profile_completed_at' => null,
        ]);
        config(['services.merchant.tilled_ach_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_cc_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_account' => fake()->uuid()]);

        Merchant::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'merchant_name' => MerchantName::YOU_NEGOTIATE,
        ]);

        $data = [
            'merchant_name' => MerchantName::YOU_NEGOTIATE->value,
            'account_holder_name' => fake()->name(),
            'bank_account_number' => fake()->numberBetween(1000000, 999999999),
            'bank_name' => fake()->name(),
            'bank_routing_number' => '021000021',
            'bank_account_type' => fake()->randomElement(BankAccountType::values()),
            'average_transaction_amount' => fake()->randomNumber(),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'industry_type' => $industryType = fake()->randomElement(IndustryType::values()),
            'legal_name' => fake()->name(),
            'contact_city' => fake()->city(),
            'contact_state' => fake()->randomElement(State::values()),
            'contact_address' => fake()->address(),
            'contact_zip' => fake()->randomNumber(5),
            'dob' => fake()->date(),
            'first_name' => fake()->name(),
            'job_title' => 'CEO',
            'last_name' => fake()->name(),
            'percentage_shareholding' => fake()->numberBetween(1, 100),
            'ssn' => in_array($industryType, IndustryType::ssnIsNotRequired()) ? fake()->randomNumber(9) : '',
            'statement_descriptor' => fake()->word(),
            'fed_tax_id' => fake()->randomNumber(9),
            'yearly_volume_range' => fake()->randomElement(YearlyVolumeRange::values()),
        ];

        $accountId = fake()->uuid();

        Http::fake(fn () => Http::response(['id' => $accountId]));

        $response = app(MerchantService::class)->verify($this->user->company, $data);

        $this->assertEquals($accountId, $this->user->company->refresh()->tilled_merchant_account_id);
        $this->assertNotNull($this->user->company->tilled_profile_completed_at);

        $this->assertTrue($response);
    }

    #[Test]
    public function it_can_store_and_verify_false_tilled_merchant(): void
    {
        $this->user->company->update([
            'tilled_merchant_account_id' => null,
            'tilled_profile_completed_at' => null,
        ]);
        config(['services.merchant.tilled_ach_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_cc_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_account' => fake()->uuid()]);

        Http::fake(fn () => Http::response([
            'statusCode' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Authorization Required',
            'error' => 'Unauthorized',
        ], Response::HTTP_UNAUTHORIZED));

        Merchant::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'merchant_name' => MerchantName::YOU_NEGOTIATE,
        ]);

        $data = [
            'merchant_name' => MerchantName::YOU_NEGOTIATE->value,
            'account_holder_name' => fake()->name(),
            'bank_account_number' => fake()->numberBetween(1000000, 999999999),
            'bank_name' => fake()->name(),
            'bank_routing_number' => '021000021',
            'bank_account_type' => fake()->randomElement(BankAccountType::values()),
        ];

        $isVerified = app(MerchantService::class)->verify($this->user->company, $data);

        $this->assertNull($this->user->company->refresh()->tilled_merchant_account_id);
        $this->assertNull($this->user->company->tilled_profile_completed_at);

        $this->assertFalse($isVerified);
    }

    #[Test]
    public function it_can_store_and_verify_true_tilled_merchant_when_tilled_merchant_account_id_not_null(): void
    {
        $this->user->company->update([
            'tilled_merchant_account_id' => $accountId = fake()->uuid(),
            'tilled_profile_completed_at' => null,
        ]);
        config(['services.merchant.tilled_ach_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_cc_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_account' => fake()->uuid()]);

        Merchant::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'merchant_name' => MerchantName::YOU_NEGOTIATE,
        ]);

        $data = [
            'merchant_name' => MerchantName::YOU_NEGOTIATE->value,
            'account_holder_name' => fake()->name(),
            'bank_account_number' => fake()->numberBetween(1000000, 999999999),
            'bank_name' => fake()->name(),
            'bank_routing_number' => '021000021',
            'bank_account_type' => fake()->randomElement(BankAccountType::values()),
            'average_transaction_amount' => fake()->randomNumber(),
            'legal_name' => fake()->name(),
            'industry_type' => $industryType = fake()->randomElement(IndustryType::values()),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'contact_city' => fake()->city(),
            'contact_state' => fake()->randomElement(State::values()),
            'contact_address' => fake()->address(),
            'contact_zip' => fake()->randomNumber(5),
            'dob' => fake()->date(),
            'first_name' => fake()->name(),
            'job_title' => 'CEO',
            'last_name' => fake()->name(),
            'percentage_shareholding' => fake()->numberBetween(1, 100),
            'ssn' => in_array($industryType, IndustryType::ssnIsNotRequired()) ? fake()->randomNumber(9) : '',
            'statement_descriptor' => fake()->word(),
            'fed_tax_id' => fake()->randomNumber(9),
            'yearly_volume_range' => fake()->randomElement(YearlyVolumeRange::values()),
        ];

        Http::fake(fn () => Http::response(['id' => $accountId]));

        $isVerified = app(MerchantService::class)->verify($this->user->company, $data);

        $this->assertNotNull($this->user->company->tilled_profile_completed_at);

        $this->assertTrue($isVerified);
    }

    #[Test]
    public function it_can_store_and_verify_false_tilled_merchant_when_tilled_merchant_account_id_not_null(): void
    {
        $this->user->company->update([
            'tilled_merchant_account_id' => fake()->uuid(),
            'tilled_profile_completed_at' => null,
        ]);
        config(['services.merchant.tilled_ach_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_cc_pricing_template_id' => fake()->uuid()]);
        config(['services.merchant.tilled_account' => fake()->uuid()]);

        Merchant::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'merchant_name' => MerchantName::YOU_NEGOTIATE,
        ]);

        $data = [
            'merchant_name' => MerchantName::YOU_NEGOTIATE->value,
            'account_holder_name' => fake()->name(),
            'bank_account_number' => fake()->numberBetween(1000000, 999999999),
            'bank_name' => fake()->name(),
            'bank_routing_number' => '021000021',
            'bank_account_type' => fake()->randomElement(BankAccountType::values()),
            'industry_type' => $industryType = fake()->randomElement(IndustryType::values()),
            'company_category' => fake()->randomElement(CompanyCategory::values()),
            'average_transaction_amount' => fake()->randomNumber(),
            'legal_name' => fake()->name(),
            'contact_city' => fake()->city(),
            'contact_state' => fake()->randomElement(State::values()),
            'contact_address' => fake()->address(),
            'contact_zip' => fake()->randomNumber(5),
            'dob' => fake()->date(),
            'first_name' => fake()->name(),
            'job_title' => 'CEO',
            'last_name' => fake()->name(),
            'percentage_shareholding' => fake()->numberBetween(1, 100),
            'ssn' => in_array($industryType, IndustryType::ssnIsNotRequired()) ? fake()->randomNumber(9) : '',
            'statement_descriptor' => fake()->word(),
            'fed_tax_id' => fake()->randomNumber(9),
            'yearly_volume_range' => fake()->randomElement(YearlyVolumeRange::values()),
        ];

        $isVerified = app(MerchantService::class)->verify($this->user->company, $data);

        $this->assertNull($this->user->company->tilled_profile_completed_at);

        $this->assertFalse($isVerified);
    }
}
