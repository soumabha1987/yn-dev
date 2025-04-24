<?php

declare(strict_types=1);

namespace Tests\Feature\AccountProfile;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\MembershipTransactionStatus;
use App\Enums\State;
use App\Livewire\Creditor\AccountProfile\BillingDetails;
use App\Models\CompanyMembership;
use App\Models\MembershipPaymentProfile;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Rules\AddressSingleSpace;
use App\Rules\AlphaSingleSpace;
use App\Services\TilledPaymentService;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingDetailsTest extends TestCase
{
    protected User $user;

    protected CompanyMembership $companyMembership;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->companyMembership = CompanyMembership::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => CompanyMembershipStatus::INACTIVE,
        ]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(BillingDetails::class)
            ->assertViewIs('livewire.creditor.account-profile.billing-details')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_call_store_validation_errors_message(array $requestData, array $requestErrorMessages): void
    {
        Livewire::actingAs($this->user)
            ->test(BillingDetails::class)
            ->assertSet('displaySuccessModal', false)
            ->set($requestData)
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', fake()->randomNumber(4))
            ->set('form.tilled_response', [
                'id' => fake()->uuid(),
                'card' => [
                    'last4' => fake()->randomNumber(4, true),
                    'exp_month' => '2',
                    'exp_year' => '2034',
                ],
            ])
            ->call('storeMembershipBillingDetails')
            ->assertHasErrors($requestErrorMessages)
            ->assertNotDispatched('close-confirm-box')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('tilledResponseValidation')]
    public function it_can_call_store_tilled_response_validation_errors_message(array $tilledCardResponse, array $errorMessage): void
    {
        Livewire::actingAs($this->user)
            ->test(BillingDetails::class)
            ->assertSet('displaySuccessModal', false)
            ->set('form.state', fake()->randomElement(State::values()))
            ->set('form.zip', fake()->randomNumber(4))
            ->set('form.tilled_response', [
                'id' => fake()->uuid(),
                'card' => $tilledCardResponse,
            ])
            ->call('storeMembershipBillingDetails')
            ->assertHasErrors($errorMessage)
            ->assertNotDispatched('close-confirm-box')
            ->assertOk();
    }

    #[Test]
    public function it_can_store_the_membership_billing_details(): void
    {
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        intval($this->companyMembership->membership->price * 100);

        $tilledPaymentMethodId = fake()->uuid();

        $tilledCustomerId = fake()->uuid();

        $this->partialMock(TilledPaymentService::class, function (MockInterface $mock) use ($tilledCustomerId): void {
            $mock->shouldReceive('createOrUpdateCustomer')
                ->once()
                ->withAnyArgs()
                ->andReturn($tilledCustomerId);

            $mock->shouldReceive('createPaymentIntents')
                ->once()
                ->withAnyArgs()
                ->andReturn(['status' => 'succeeded']);
        });

        Livewire::actingAs($this->user)
            ->test(BillingDetails::class)
            ->assertSet('displaySuccessModal', false)
            ->set('form.first_name', $firstName = 'FirstName')
            ->set('form.last_name', $lastName = 'LastName')
            ->set('form.address', $address = 'Testing address, 1/2 pages')
            ->set('form.city', $city = 'Testing city')
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(4))
            ->set('form.tilled_response', [
                'id' => $tilledPaymentMethodId,
                'card' => [
                    'last4' => $lastFourDigit = fake()->randomNumber(4, true),
                    'exp_month' => '2',
                    'exp_year' => '2034',
                ],
            ])
            ->set('form.acceptTermsAndConditions', true)
            ->call('storeMembershipBillingDetails')
            ->assertHasNoErrors()
            ->assertDispatched('close-confirm-box')
            ->assertSet('displaySuccessModal', true)
            ->assertOk();

        $this->assertEquals($this->companyMembership->refresh()->status, CompanyMembershipStatus::ACTIVE);

        $this->assertDatabaseCount(MembershipTransaction::class, 1);

        $this->assertEquals($this->user->company->refresh()->current_step, CreditorCurrentStep::COMPLETED->value);
        $this->assertEquals($this->user->company->billing_address, $address);
        $this->assertEquals($this->user->company->billing_city, $city);
        $this->assertEquals($this->user->company->billing_state, $state);
        $this->assertEquals($this->user->company->billing_zip, $zip);
        $this->assertNotNull($this->user->company->approved_by);
        $this->assertNotNull($this->user->company->approved_at);

        $this->assertDatabaseHas(MembershipPaymentProfile::class, [
            'company_id' => $this->user->company_id,
            'tilled_payment_method_id' => $tilledPaymentMethodId,
            'tilled_customer_id' => $tilledCustomerId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'expiry' => '02/2034',
            'last_four_digit' => $lastFourDigit,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'response->status' => 'succeeded',
        ]);

        $this->assertTrue(MembershipTransaction::query()
            ->where([
                'company_id' => $this->user->company_id,
                'membership_id' => $this->companyMembership->membership_id,
                'status' => MembershipTransactionStatus::SUCCESS,
            ])
            ->whereNotNull('plan_end_date')
            ->exists());
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'form.first_name' => str('a')->repeat(21),
                    'form.last_name' => str('a')->repeat(31),
                    'form.address' => str('a')->repeat(101),
                    'form.city' => str('a')->repeat(31),
                ],
                [
                    'form.first_name' => ['max:20'],
                    'form.last_name' => ['max:30'],
                    'form.address' => ['max:100'],
                    'form.city' => ['max:30'],
                ],
            ],
            [
                [
                    'form.first_name' => 'a',
                    'form.last_name' => 'a',
                    'form.address' => 'a',
                    'form.city' => 'a',
                ],
                [
                    'form.first_name' => ['min:2'],
                    'form.last_name' => ['min:2'],
                    'form.address' => ['min:2'],
                    'form.city' => ['min:2'],
                ],
            ],
            [
                [
                    'form.first_name' => 'first name',
                    'form.last_name' => 'last name',
                    'form.address' => 'testing      address',
                    'form.city' => 'testing     city',
                ],
                [
                    'form.first_name' => ['alpha:ascii'],
                    'form.last_name' => ['alpha:ascii'],
                    'form.address' => [AddressSingleSpace::class],
                    'form.city' => [AlphaSingleSpace::class],
                ],
            ],
            [
                [
                    'form.first_name' => 'first007',
                    'form.last_name' => 'last007',
                    'form.address' => '12345678',
                    'form.city' => '12345678',
                ],
                [
                    'form.first_name' => ['alpha:ascii'],
                    'form.last_name' => ['alpha:ascii'],
                    'form.address' => [AddressSingleSpace::class],
                    'form.city' => [AlphaSingleSpace::class],
                ],
            ],
            [
                [
                    'form.first_name' => 'first@#$%#^$^$^',
                    'form.last_name' => 'last@%#$^%&^*&',
                    'form.address' => 'Testing Address part 1/2/4/5',
                    'form.city' => 'Testing City 123',
                ],
                [
                    'form.first_name' => ['alpha:ascii'],
                    'form.last_name' => ['alpha:ascii'],
                    'form.city' => [AlphaSingleSpace::class],
                ],
            ],
        ];
    }

    public static function tilledResponseValidation(): array
    {
        return [
            [
                [
                    'last4' => '',
                    'exp_month' => '',
                    'exp_year' => '',
                ],
                [
                    'form.tilled_response.card.last4' => ['required'],
                    'form.tilled_response.card.exp_month' => ['required'],
                    'form.tilled_response.card.exp_year' => ['required'],
                ],
            ],
            [
                [
                    'last4' => 'abcde',
                    'exp_month' => 'abcde',
                    'exp_year' => 'abcde',
                ],
                [
                    'form.tilled_response.card.last4' => ['numeric'],
                    'form.tilled_response.card.exp_month' => ['integer'],
                    'form.tilled_response.card.exp_year' => ['integer'],
                ],
            ],
            [
                [
                    'last4' => '12345',
                    'exp_month' => '12345',
                    'exp_year' => '12345',
                ],
                [
                    'form.tilled_response.card.last4' => ['digits:4'],
                    'form.tilled_response.card.exp_month' => ['max_digits:2'],
                    'form.tilled_response.card.exp_year' => ['digits:4'],
                ],
            ],
            [
                [
                    'last4' => '0',
                    'exp_month' => '0',
                    'exp_year' => '0',
                ],
                [
                    'form.tilled_response.card.last4' => ['digits:4'],
                    'form.tilled_response.card.exp_month' => ['min:1'],
                    'form.tilled_response.card.exp_year' => ['digits:4'],
                ],
            ],
            [
                [
                    'last4' => '1234',
                    'exp_month' => '13',
                    'exp_year' => '2000',
                ],
                [
                    'form.tilled_response.card.exp_month' => ['max:12'],
                ],
            ],
        ];
    }
}
