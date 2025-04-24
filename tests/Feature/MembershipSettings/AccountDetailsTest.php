<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipSettings;

use AllowDynamicProperties;
use App\Livewire\Creditor\MembershipSettings\AccountDetails;
use App\Models\MembershipPaymentProfile;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class AccountDetailsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->membershipPaymentProfile = MembershipPaymentProfile::factory()
            ->create([
                'company_id' => $this->user->company_id,
            ]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_details(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountDetails::class)
            ->assertSet('membershipPaymentProfile.id', $this->membershipPaymentProfile->id)
            ->assertSet('first_name', $this->membershipPaymentProfile->first_name)
            ->assertSet('last_name', $this->membershipPaymentProfile->last_name)
            ->assertSet('last_four_digit_of_card_number', $this->membershipPaymentProfile->last_four_digit)
            ->assertSet('zip', $this->membershipPaymentProfile->zip)
            ->assertOk();
    }

    #[Test]
    public function it_can_update_company_details(): void
    {
        $customerId = fake()->uuid();
        Http::fake(fn (): array => ['id' => $customerId]);

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        Livewire::actingAs($this->user)
            ->test(AccountDetails::class)
            ->set('first_name', $firstName = fake()->firstName())
            ->set('last_name', $lastName = fake()->lastName())
            ->set('tilled_response', $tilledResponse = [
                'id' => fake()->uuid(),
                'card' => [
                    'last4' => (string) fake()->randomNumber(4, true),
                    'exp_month' => '12',
                    'exp_year' => '2028',
                ],
            ])
            ->call('updateDetails')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotified(__('Your payment method has been updated!'));

        $this->assertEquals($firstName, $this->membershipPaymentProfile->refresh()->first_name);
        $this->assertEquals($lastName, $this->membershipPaymentProfile->last_name);
        $this->assertEquals($tilledResponse['card']['last4'], $this->membershipPaymentProfile->last_four_digit);
        $this->assertEquals($tilledResponse['card']['exp_month'] . '/' . $tilledResponse['card']['exp_year'], $this->membershipPaymentProfile->expiry);
        $this->assertEquals($tilledResponse['id'], $this->membershipPaymentProfile->tilled_payment_method_id);
        $this->assertEquals($customerId, $this->membershipPaymentProfile->tilled_customer_id);
        $this->assertEquals($tilledResponse, $this->membershipPaymentProfile->response);
    }
}
