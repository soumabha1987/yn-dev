<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BankAccountType;
use App\Enums\CompanyBusinessCategory;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\MerchantSettingsPage;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Rules\AddressSingleSpace;
use App\Rules\AlphaNumberSingleSpace;
use App\Rules\AlphaSingleSpace;
use App\Rules\NamingRule;
use App\Services\MerchantService;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class MerchantSettingsPageTest extends AuthTestCase
{
    protected Company $company;

    #[Test]
    public function it_can_render_livewire_page(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->setRegistrationDetails();
        $this->user->assignRole($role);
        $this->user->update(['subclient_id' => null]);

        Merchant::factory()->create([
            'verified_at' => now(),
            'subclient_id' => null,
            'company_id' => $this->company->id,
        ]);

        $this->get(route('creditor.merchant-settings'))
            ->assertSeeLivewire(MerchantSettingsPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_update_using_stripe_merchant(): void
    {
        $this->setRegistrationDetails();

        $merchant = Merchant::factory()->create([
            'merchant_name' => $merchantName = MerchantName::STRIPE->value,
            'verified_at' => $now = now(),
            'subclient_id' => null,
            'company_id' => $this->company->id,
        ]);

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', $merchantName)
            ->set('stripeForm.stripe_secret_key', $secretKey = 'Test_Secret_Key')
            ->call('updateMerchantSettings');

        $this->assertModelExists($merchant)
            ->assertSame($merchant->refresh()->verified_at->toDateTimeString(), $now->toDateTimeString());
        $this->assertNotEquals($merchant->stripe_secret_key, $secretKey);
    }

    #[Test]
    public function it_can_check_validation(): void
    {
        $this->createMerchants();

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', '')
            ->call('updateMerchantSettings')
            ->assertSee(__('validation.required', ['attribute' => 'merchant name']));
    }

    #[Test]
    public function it_can_not_update_merchant(): void
    {
        $this->setRegistrationDetails();

        [$ccMerchant, $achMerchant] = $this->createMerchants();

        $this->partialMock(MerchantService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->never()->andReturn(true);
        });

        Consumer::factory()->for($this->company)->create(['status' => ConsumerStatus::PAYMENT_SETUP]);

        Livewire::test(MerchantSettingsPage::class)
            ->assertSet('isNotEditable', true)
            ->set('merchant_name', MerchantName::STRIPE)
            ->set('stripeForm.stripe_secret_key', 'test')
            ->call('updateMerchantSettings');

        $this->assertModelExists($ccMerchant)
            ->assertModelExists($achMerchant)
            ->assertDatabaseMissing(Merchant::class, [
                'merchant_name' => MerchantName::STRIPE->value,
                'stripe_secret_key' => 'test',
                'merchant_type' => MerchantType::CC->value,
            ]);
    }

    #[Test]
    public function it_can_update_merchant_settings_using_stripe_merchant(): void
    {
        $this->setRegistrationDetails();

        [$ccMerchant, $achMerchant] = $this->createMerchants();

        $this->partialMock(MerchantService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::STRIPE)
            ->set('stripeForm.stripe_secret_key', 'test')
            ->call('updateMerchantSettings');

        $this->assertModelMissing($ccMerchant)
            ->assertModelMissing($achMerchant)
            ->assertDatabaseHas(Merchant::class, [
                'merchant_name' => MerchantName::STRIPE->value,
                'stripe_secret_key' => 'test',
                'merchant_type' => MerchantType::CC->value,
            ]);
    }

    #[Test]
    public function it_can_update_validation_error_using_authorize_merchant(): void
    {
        $this->createMerchants();

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::AUTHORIZE)
            ->set('authorizeForm.merchant_type', [])
            ->set('authorizeForm.authorize_login_id', '')
            ->set('authorizeForm.authorize_transaction_key', '')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasErrors([
                'authorizeForm.merchant_type' => __('Please select at least one payment method.'),
                'authorizeForm.authorize_login_id' => ['required'],
                'authorizeForm.authorize_transaction_key' => ['required'],
            ]);
    }

    #[Test]
    public function it_can_update_merchant_settings_using_authorize_merchant(): void
    {
        [$ccMerchant, $achMerchant] = $this->createMerchants();

        $this->partialMock(MerchantService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::AUTHORIZE)
            ->set('authorizeForm.merchant_type', [MerchantType::CC->value])
            ->set('authorizeForm.authorize_login_id', 'test')
            ->set('authorizeForm.authorize_transaction_key', 'test')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($achMerchant)
            ->assertModelMissing($ccMerchant)
            ->assertDatabaseHas(Merchant::class, [
                'merchant_name' => MerchantName::AUTHORIZE->value,
                'merchant_type' => MerchantType::CC->value,
                'authorize_login_id' => 'test',
                'authorize_transaction_key' => 'test',
            ]);
    }

    #[Test]
    public function it_can_update_validation_error_using_usa_epay_merchant(): void
    {
        $this->createMerchants();

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::USA_EPAY)
            ->set('usaEpayForm.merchant_type', [])
            ->set('usaEpayForm.usaepay_key', '')
            ->set('usaEpayForm.usaepay_pin', '')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasErrors([
                'usaEpayForm.merchant_type' => __('Please select at least one payment method.'),
                'usaEpayForm.usaepay_key' => ['required'],
                'usaEpayForm.usaepay_pin' => ['required'],
            ]);
    }

    #[Test]
    public function it_can_update_merchant_settings_using_usa_epay(): void
    {
        [$ccMerchant, $achMerchant] = $this->createMerchants();

        $this->partialMock(MerchantService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::USA_EPAY)
            ->set('usaEpayForm.merchant_type', MerchantType::values())
            ->set('usaEpayForm.usaepay_key', 'test')
            ->set('usaEpayForm.usaepay_pin', 'test')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($achMerchant)
            ->assertModelMissing($ccMerchant)
            ->assertDatabaseHas(Merchant::class, [
                'merchant_name' => MerchantName::USA_EPAY->value,
                'merchant_type' => fake()->randomElement(MerchantType::values()),
                'usaepay_key' => 'test',
                'usaepay_pin' => 'test',
            ]);
    }

    #[Test]
    public function it_can_update_merchant_settings_using_tilled(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->user->update(['subclient_id' => null]);

        [$ccMerchant, $achMerchant] = $this->createMerchants();

        $this->company->tilled_profile_completed_at = now();
        $this->company->save();

        $this->partialMock(MerchantService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::YOU_NEGOTIATE)
            ->set('tilledForm.account_holder_name', 'Test Account holder')
            ->set('tilledForm.bank_name', 'Test Bank name')
            ->set('tilledForm.bank_account_type', $bankAccountType = fake()->randomElement(BankAccountType::values()))
            ->set('tilledForm.bank_account_number', '1234')
            ->set('tilledForm.bank_routing_number', '021000021')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($achMerchant)
            ->assertModelMissing($ccMerchant)
            ->assertDatabaseHas(Merchant::class, [
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'merchant_name' => MerchantName::YOU_NEGOTIATE->value,
                'merchant_type' => fake()->randomElement(MerchantType::values()),
            ]);

        $this->assertSame($this->company->refresh()->bank_account_number, '34');
        $this->assertSame($this->company->bank_routing_number, '021000021');
        $this->assertSame($this->company->bank_account_type->value, $bankAccountType);
    }

    #[Test]
    #[DataProvider('requestTilledValidation')]
    public function it_can_create_tilled_merchant_validation(array $requestTilledData, array $requestErrorMessages): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->user->update(['subclient_id' => null]);

        $this->user->company->update([
            'status' => CompanyStatus::CREATED,
            'business_category' => CompanyBusinessCategory::THIRD_PARTY_DEBT_SERVICE,
            'tilled_profile_completed_at' => null,
        ]);

        Livewire::test(MerchantSettingsPage::class)
            ->set('merchant_name', MerchantName::YOU_NEGOTIATE->value)
            ->set($requestTilledData)
            ->set('tilledForm.bank_account_type', fake()->randomElement(BankAccountType::values()))
            ->set('tilledForm.bank_account_number', '1234')
            ->set('tilledForm.bank_routing_number', '021000021')
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasErrors($requestErrorMessages);
    }

    #[Test]
    public function it_can_not_allow_you_negotiate_merchant(): void
    {
        $company = Company::factory()->create([
            'current_step' => CreditorCurrentStep::COMPLETED,
            'approved_at' => now(),
            'business_category' => CompanyBusinessCategory::THIRD_PARTY_COLLECTION_LAW_FIRM,
        ]);

        $this->user->company_id = $company->id;
        $this->user->save();

        Livewire::actingAs($this->user)
            ->test(MerchantSettingsPage::class)
            ->set('isNotEditable', false)
            ->set('merchant_name', MerchantName::YOU_NEGOTIATE->value)
            ->call('updateMerchantSettings')
            ->assertOk()
            ->assertHasNoErrors();

        Notification::assertNotified(__('Your company\'s business category is not allowed to use the YouNegotiate merchant.'));
    }

    private function createMerchants(): array
    {
        $this->setRegistrationDetails();

        return Merchant::factory(2)
            ->sequence(
                ['merchant_type' => MerchantType::CC->value],
                ['merchant_type' => MerchantType::ACH->value]
            )
            ->create([
                'merchant_name' => MerchantName::AUTHORIZE->value,
                'verified_at' => now(),
                'subclient_id' => null,
                'company_id' => $this->company->id,
            ])
            ->all();
    }

    private function setRegistrationDetails(): void
    {
        $this->company = Company::factory()->create([
            'current_step' => CreditorCurrentStep::COMPLETED,
            'approved_at' => now(),
            'business_category' => CompanyBusinessCategory::THIRD_PARTY_DEBT_SERVICE,
        ]);

        $this->user->company_id = $this->company->id;

        $this->user->save();

        $this->user->refresh();

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);
    }

    public static function requestTilledValidation(): array
    {
        return [
            [
                [
                    'tilledForm.account_holder_name' => 'a',
                    'tilledForm.bank_name' => 'a',
                    'tilledForm.legal_name' => 'a',
                    'tilledForm.statement_descriptor' => 'a',
                    'tilledForm.first_name' => 'a',
                    'tilledForm.last_name' => 'a',
                    'tilledForm.job_title' => 'a',
                    'tilledForm.contact_address' => 'a',
                    'tilledForm.contact_city' => 'a',
                ],
                [
                    'tilledForm.account_holder_name' => ['min:2'],
                    'tilledForm.bank_name' => ['min:2'],
                    'tilledForm.legal_name' => ['min:2'],
                    'tilledForm.statement_descriptor' => ['min:2'],
                    'tilledForm.first_name' => ['min:2'],
                    'tilledForm.last_name' => ['min:2'],
                    'tilledForm.job_title' => ['min:2'],
                    'tilledForm.contact_address' => ['min:2'],
                    'tilledForm.contact_city' => ['min:2'],
                ],
            ],
            [
                [
                    'tilledForm.account_holder_name' => str('a')->repeat(51),
                    'tilledForm.bank_name' => str('b')->repeat(51),
                    'tilledForm.legal_name' => str('c')->repeat(101),
                    'tilledForm.statement_descriptor' => str('d')->repeat(21),
                    'tilledForm.first_name' => str('e')->repeat(21),
                    'tilledForm.last_name' => str('f')->repeat(31),
                    'tilledForm.job_title' => str('g')->repeat(31),
                    'tilledForm.contact_address' => str('h')->repeat(101),
                    'tilledForm.contact_city' => str('i')->repeat(31),
                ],
                [
                    'tilledForm.account_holder_name' => ['max:50'],
                    'tilledForm.bank_name' => ['max:50'],
                    'tilledForm.legal_name' => ['max:100'],
                    'tilledForm.statement_descriptor' => ['max:20'],
                    'tilledForm.first_name' => ['max:20'],
                    'tilledForm.last_name' => ['max:30'],
                    'tilledForm.job_title' => ['max:30'],
                    'tilledForm.contact_address' => ['max:100'],
                    'tilledForm.contact_city' => ['max:30'],
                ],
            ],
            [
                [
                    'tilledForm.account_holder_name' => 'test    account    holder',
                    'tilledForm.bank_name' => 'test    bank    name',
                    'tilledForm.legal_name' => 'test    legal    name',
                    'tilledForm.statement_descriptor' => 'test   statement   des',
                    'tilledForm.first_name' => 'test   first  name',
                    'tilledForm.last_name' => 'test   last  name',
                    'tilledForm.job_title' => 'test   job  title',
                    'tilledForm.contact_address' => 'test  contact  address',
                    'tilledForm.contact_city' => 'test  contact  city',
                ],
                [
                    'tilledForm.account_holder_name' => [NamingRule::class],
                    'tilledForm.bank_name' => [AlphaNumberSingleSpace::class],
                    'tilledForm.legal_name' => [AlphaNumberSingleSpace::class],
                    'tilledForm.statement_descriptor' => [AlphaNumberSingleSpace::class],
                    'tilledForm.first_name' => [NamingRule::class],
                    'tilledForm.last_name' => [NamingRule::class],
                    'tilledForm.job_title' => [AlphaSingleSpace::class],
                    'tilledForm.contact_address' => [AddressSingleSpace::class],
                    'tilledForm.contact_city' => [AlphaSingleSpace::class],
                ],
            ],
            [
                [
                    'tilledForm.account_holder_name' => 'Account @ holder 123',
                    'tilledForm.bank_name' => 'Bank @ name 123',
                    'tilledForm.legal_name' => 'Legal @ name 123',
                    'tilledForm.statement_descriptor' => 'Statement @ descriptor',
                    'tilledForm.first_name' => 'First @ name',
                    'tilledForm.last_name' => 'Last @ name',
                    'tilledForm.job_title' => 'Job @ title',
                    'tilledForm.contact_address' => 'Contact @ address',
                    'tilledForm.contact_city' => 'Contact @ city',
                ],
                [
                    'tilledForm.account_holder_name' => [NamingRule::class],
                    'tilledForm.bank_name' => [AlphaNumberSingleSpace::class],
                    'tilledForm.legal_name' => [AlphaNumberSingleSpace::class],
                    'tilledForm.statement_descriptor' => [AlphaNumberSingleSpace::class],
                    'tilledForm.first_name' => [NamingRule::class],
                    'tilledForm.last_name' => [NamingRule::class],
                    'tilledForm.job_title' => [AlphaSingleSpace::class],
                    'tilledForm.contact_city' => [AlphaSingleSpace::class],
                ],
            ],
        ];
    }
}
