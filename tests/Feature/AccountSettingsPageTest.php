<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyBusinessCategory;
use App\Enums\CreditorCurrentStep;
use App\Enums\DebtType;
use App\Enums\Role as EnumRole;
use App\Enums\State;
use App\Enums\Timezone;
use App\Livewire\Creditor\AccountSettingsPage;
use App\Models\Company;
use App\Rules\AddressSingleSpace;
use App\Rules\AlphaSingleSpace;
use App\Rules\NamingRule;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class AccountSettingsPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_page_of_account_settings(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.settings'))
            ->assertSeeLivewire(AccountSettingsPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_account_settings(): void
    {
        Livewire::test(AccountSettingsPage::class)
            ->assertViewIs('livewire.creditor.account-settings-page')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('requestValidationForm')]
    public function it_can_render_validation_errors(array $requestData, array $requestErrors): void
    {
        Livewire::test(AccountSettingsPage::class)
            ->set($requestData)
            ->call('updateSettings')
            ->assertOk()
            ->assertHasErrors($requestErrors);
    }

    #[Test]
    public function it_can_display_the_records_of_the_logged_in_user(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $image = UploadedFile::fake()->create('test.png');

        $this->assertNull($this->user->image);

        Livewire::test(AccountSettingsPage::class)
            ->set('form.owner_full_name', $fullName = 'owner full name')
            ->set('form.owner_email', $ownerEmail = 'owener@email.com')
            ->set('form.owner_phone', $ownerPhone = '9008990089')
            ->set('form.company_name', $companyName = '7 up company')
            ->set('form.billing_email', $billingEmail = 'billing@email.com')
            ->set('form.billing_phone', $billingPhone = '9005090050')
            ->set('form.business_category', $businessCategory = fake()->randomElement((CompanyBusinessCategory::values())))
            ->set('form.debt_type', $debtType = fake()->randomElement((DebtType::values())))
            ->set('form.fed_tax_id', $fedTaxId = '123123123')
            ->set('form.timezone', $timezone = fake()->randomElement((Timezone::values())))
            ->set('form.url', $url = 'http://example.com')
            ->set('form.from_time', $fromTime = fake()->time('g:i A'))
            ->set('form.to_time', $toTime = fake()->time('g:i A'))
            ->set('form.from_day', $fromDay = fake()->numberBetween('0', '6'))
            ->set('form.to_day', $toDay = fake()->numberBetween('0', '6'))
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zipCode = fake()->randomNumber(5, strict: true))
            ->set('image', $image)
            ->call('updateSettings')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNotDispatched('update-user-avatar');

        Notification::assertNotified(__('Account profile updated successfully!'));

        $this->assertNotNull($this->user->image);

        $this->assertDatabaseHas(Company::class, [
            'id' => $this->user->company_id,
            'owner_full_name' => $fullName,
            'owner_email' => $ownerEmail,
            'owner_phone' => $ownerPhone,
            'company_name' => $companyName,
            'billing_phone' => $billingPhone,
            'billing_email' => $billingEmail,
            'timezone' => $timezone,
            'business_category' => $businessCategory,
            'debt_type' => $debtType,
            'fed_tax_id' => $fedTaxId,
            'url' => $url,
            'from_time' => Carbon::parse($fromTime, $timezone)->utc()->format('H:i:s'),
            'to_time' => Carbon::parse($toTime, $timezone)->utc()->format('H:i:s'),
            'from_day' => $fromDay,
            'to_day' => $toDay,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zipCode,
        ]);
    }

    public static function requestValidationForm(): array
    {
        return [
            [
                [
                    'form.owner_full_name' => '',
                    'form.owner_email' => '',
                    'form.owner_phone' => '',
                    'form.company_name' => '',
                    'form.billing_email' => '',
                    'form.billing_phone' => '',
                    'form.timezone' => '',
                    'form.business_category' => '',
                    'form.debt_type' => '',
                    'form.url' => '',
                    'form.from_time' => '',
                    'form.to_time' => '',
                    'form.from_day' => '',
                    'form.to_day' => '',
                    'form.address' => '',
                    'form.city' => '',
                    'form.state' => '',
                    'form.zip' => '',
                ],
                [
                    'form.owner_full_name' => ['required'],
                    'form.owner_email' => ['required'],
                    'form.owner_phone' => ['required'],
                    'form.company_name' => ['required'],
                    'form.billing_email' => ['required'],
                    'form.billing_phone' => ['required'],
                    'form.timezone' => ['required'],
                    'form.business_category' => ['required'],
                    'form.debt_type' => ['required'],
                    'form.url' => ['required'],
                    'form.from_time' => ['required'],
                    'form.to_time' => ['required'],
                    'form.from_day' => ['required'],
                    'form.to_day' => ['required'],
                    'form.address' => ['required'],
                    'form.city' => ['required'],
                    'form.state' => ['required'],
                    'form.zip' => ['required'],
                ],
            ],
            [
                [
                    'form.owner_full_name' => str('a')->repeat(26),
                    'form.owner_email' => str('a')->repeat(52),
                    'form.owner_phone' => str('a')->repeat(12),
                    'form.company_name' => str('a')->repeat(52),
                    'form.billing_email' => str('a')->repeat(52),
                    'form.billing_phone' => str('a')->repeat(12),
                    'form.timezone' => str('a')->repeat(12),
                    'form.business_category' => str('a')->repeat(101),
                    'form.debt_type' => str('a')->repeat(51),
                    'form.fed_tax_id' => str('a')->repeat(9),
                    'form.url' => str('a')->repeat(12),
                    'form.from_day' => str('a')->repeat(12),
                    'form.to_day' => str('a')->repeat(12),
                    'form.address' => str('a')->repeat(101),
                    'form.city' => str('a')->repeat(32),
                    'form.state' => str('a')->repeat(12),
                    'form.zip' => str('a')->repeat(6),
                ],
                [
                    'form.owner_full_name' => ['max:25'],
                    'form.owner_email' => ['max:50'],
                    'form.owner_phone' => ['phone:US'],
                    'form.company_name' => ['max:50'],
                    'form.billing_email' => ['max:50'],
                    'form.billing_phone' => ['phone:US'],
                    'form.timezone' => ['max:5'],
                    'form.business_category' => ['max:100'],
                    'form.debt_type' => ['max:50'],
                    'form.fed_tax_id' => ['numeric'],
                    'form.from_day' => ['integer'],
                    'form.to_day' => ['integer'],
                    'form.address' => ['max:100'],
                    'form.city' => ['max:30'],
                    'form.state' => ['max:10'],
                    'form.zip' => ['max_digits:5'],
                ],
            ],
            [
                [
                    'form.owner_full_name' => 'a',
                    'form.owner_email' => 'a',
                    'form.company_name' => 'a',
                    'form.billing_email' => 'a',
                    'form.timezone' => 'a',
                    'form.business_category' => 'a',
                    'form.debt_type' => 'a',
                    'form.url' => 'a',
                    'form.from_day' => 'a',
                    'form.to_day' => 'a',
                    'form.address' => 'a',
                    'form.city' => 'a',
                    'form.state' => 'a',
                    'form.zip' => 'a',
                ],
                [
                    'form.owner_full_name' => ['min:3'],
                    'form.owner_email' => ['email'],
                    'form.company_name' => ['min:3'],
                    'form.billing_email' => ['email'],
                    'form.timezone' => ['in'],
                    'form.business_category' => ['in'],
                    'form.debt_type' => ['in'],
                    'form.from_day' => ['integer'],
                    'form.to_day' => ['integer'],
                    'form.address' => ['min:2'],
                    'form.city' => ['min:2'],
                    'form.state' => ['in'],
                    'form.zip' => ['numeric'],
                ],
            ],
            [
                [
                    'form.owner_full_name' => 'test       owner name',
                    'form.company_name' => 'test      company name',
                    'form.address' => 'test         company address',
                    'form.city' => 'test  city',
                    'form.fed_tax_id' => 123123,
                    'form.from_time' => fake()->time('h:i'),
                    'form.to_time' => fake()->time('H:i'),
                ],
                [
                    'form.owner_full_name' => [NamingRule::class],
                    'form.company_name' => [NamingRule::class],
                    'form.address' => [AddressSingleSpace::class],
                    'form.city' => [AlphaSingleSpace::class],
                    'form.fed_tax_id' => ['digits:9'],
                    'form.from_time' => ['date_format:g:i A'],
                    'form.to_time' => ['date_format:g:i A'],
                ],
            ],
        ];
    }
}
