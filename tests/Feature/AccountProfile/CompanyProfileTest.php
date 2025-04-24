<?php

declare(strict_types=1);

namespace Tests\Feature\AccountProfile;

use App\Enums\CompanyBusinessCategory;
use App\Enums\DebtType;
use App\Enums\State;
use App\Enums\Timezone;
use App\Livewire\Creditor\AccountProfile\CompanyProfile;
use App\Livewire\Creditor\AccountProfile\IndexPage;
use App\Models\User;
use App\Rules\NamingRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->assertViewIs('livewire.creditor.account-profile.company-profile')
            ->assertOk();
    }

    #[Test]
    public function it_can_fill_form_of_company_profile(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->assertViewIs('livewire.creditor.account-profile.company-profile')
            ->assertSet('form.company_name', $this->user->company->company_name)
            ->assertSet('form.owner_full_name', $this->user->company->owner_full_name)
            ->assertSet('form.owner_email', $this->user->company->owner_email)
            ->assertSet('form.owner_phone', $this->user->company->owner_phone)
            ->assertSet('form.business_category', $this->user->company->business_category->value)
            ->assertSet('form.debt_type', $this->user->company->debt_type->value)
            ->assertSet('form.fed_tax_id', $this->user->company->fed_tax_id)
            ->assertSet('form.address', $this->user->company->address)
            ->assertSet('form.state', $this->user->company->state)
            ->assertSet('form.city', $this->user->company->city)
            ->assertSet('form.zip', $this->user->company->zip)
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.company_name', '')
            ->set('form.owner_full_name', '')
            ->set('form.owner_email', '')
            ->set('form.owner_phone', '')
            ->set('form.business_category', '')
            ->set('form.debt_type', '')
            ->set('form.timezone', '')
            ->set('form.url', '')
            ->set('form.from_time', '')
            ->set('form.to_time', '')
            ->set('form.from_day', '')
            ->set('form.to_day', '')
            ->set('form.address', '')
            ->set('form.city', '')
            ->set('form.state', '')
            ->set('form.zip', '')
            ->call('store')
            ->assertHasErrors([
                'form.company_name' => ['required'],
                'form.owner_full_name' => ['required'],
                'form.owner_email' => ['required'],
                'form.owner_phone' => ['required'],
                'form.business_category' => ['required'],
                'form.debt_type' => ['required'],
                'form.timezone' => ['required'],
                'form.url' => ['required'],
                'form.from_time' => ['required'],
                'form.to_time' => ['required'],
                'form.from_day' => ['required'],
                'form.to_day' => ['required'],
                'form.address' => ['required'],
                'form.city' => ['required'],
                'form.state' => ['required'],
                'form.zip' => ['required'],
            ]);
    }

    #[Test]
    #[DataProvider('nameValidation')]
    public function it_can_validation_for_company_name_and_owner_full_name(array $requestSetData, array $requestErrors): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set($requestSetData)
            ->call('store')
            ->assertHasErrors($requestErrors)
            ->assertOk();
    }

    #[Test]
    public function it_can_passed_name_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.company_name', 'Laravel Pvt ltd 1')
            ->set('form.owner_full_name', 'Laravel owner test')
            ->call('store')
            ->assertHasNoErrors(['form.company_name', 'form.owner_full_name'])
            ->assertOk();
    }

    #[Test]
    public function it_can_allow_only_us_phone_number(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.owner_phone', fake()->randomNumber(6))
            ->call('store')
            ->assertHasErrors(['form.owner_phone' => ['phone:US']])
            ->assertOk();
    }

    #[Test]
    public function it_can_only_allow_business_category_in_enum(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.business_category', fake()->word())
            ->call('store')
            ->assertHasErrors(['form.business_category' => ['in']])
            ->assertOk();
    }

    #[Test]
    public function it_can_only_allow_debt_type_in_enum(): void
    {
        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.debt_type', fake()->word())
            ->call('store')
            ->assertHasErrors(['form.debt_type' => ['in']])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_company_profile(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::actingAs($this->user)
            ->test(CompanyProfile::class)
            ->set('form.company_name', $companyName = 'testing company 1')
            ->set('form.owner_full_name', $name = 'owner full name')
            ->set('form.owner_email', $email = fake()->email())
            ->set('form.owner_phone', $phoneNumber = fake()->phoneNumber())
            ->set('form.business_category', $businessCategory = fake()->randomElement(CompanyBusinessCategory::values()))
            ->set('form.debt_type', $debtType = fake()->randomElement(DebtType::values()))
            ->set('form.fed_tax_id', '')
            ->set('form.timezone', $timezone = fake()->randomElement((Timezone::values())))
            ->set('form.url', $url = 'example.com')
            ->set('form.from_time', $fromTime = fake()->time('g:i A'))
            ->set('form.to_time', $toTime = fake()->time('g:i A'))
            ->set('form.from_day', $fromDay = fake()->numberBetween('0', '6'))
            ->set('form.to_day', $toDay = fake()->numberBetween('0', '6'))
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = str_replace("'", '', fake()->city()))
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->numberBetween(10000, 99999))
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatchedTo(IndexPage::class, 'next')
            ->assertOk();

        $this->user->company->refresh();

        $this->assertEquals($this->user->company->company_name, $companyName);
        $this->assertEquals($this->user->company->owner_full_name, $name);
        $this->assertEquals($this->user->company->owner_email, $email);
        $this->assertEquals($this->user->company->owner_phone, $phoneNumber);
        $this->assertEquals($this->user->company->business_category->value, $businessCategory);
        $this->assertEquals($this->user->company->debt_type->value, $debtType);
        $this->assertEquals($this->user->company->timezone->value, $timezone);
        $this->assertEquals($this->user->company->url, 'http://' . $url);
        $this->assertEquals($this->user->company->from_time->format('g:i A'), Carbon::parse($fromTime, $timezone)->utc()->format('g:i A'));
        $this->assertEquals($this->user->company->to_time->format('g:i A'), Carbon::parse($toTime, $timezone)->utc()->format('g:i A'));
        $this->assertEquals($this->user->company->from_day, $fromDay);
        $this->assertEquals($this->user->company->to_day, $toDay);
        $this->assertEquals($this->user->company->address, $address);
        $this->assertEquals($this->user->company->city, $city);
        $this->assertEquals($this->user->company->state, $state);
        $this->assertEquals($this->user->company->zip, $zip);
    }

    public static function nameValidation(): array
    {
        return [
            [
                [
                    'form.company_name' => 'testing     company',
                    'form.owner_full_name' => 'testing     owner   team',
                ],
                [
                    'form.company_name' => [NamingRule::class],
                    'form.owner_full_name' => [NamingRule::class],
                ],
            ],
            [
                [
                    'form.company_name' => '14536457687',
                    'form.owner_full_name' => 'Owner team #$#%1',
                ],
                [
                    'form.company_name' => [NamingRule::class],
                    'form.owner_full_name' => [NamingRule::class],
                ],
            ],
            [
                [
                    'form.company_name' => str('a')->repeat(51),
                    'form.owner_full_name' => str('a')->repeat(26),
                ],
                [
                    'form.company_name' => ['max:50'],
                    'form.owner_full_name' => ['max:25'],
                ],
            ],
            [
                [
                    'form.company_name' => 'aa',
                    'form.owner_full_name' => 'aa',
                ],
                [
                    'form.company_name' => ['min:3'],
                    'form.owner_full_name' => ['min:3'],
                ],
            ],
        ];
    }
}
