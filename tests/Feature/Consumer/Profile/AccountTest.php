<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Profile;

use AllowDynamicProperties;
use App\Enums\State;
use App\Livewire\Consumer\Profile\Account;
use App\Models\Consumer;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class AccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_the_route(): void
    {
        $this->get(route('consumer.profile'))
            ->assertOk()
            ->assertSeeLivewire(Account::class);
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(Account::class)
            ->assertViewIs('livewire.consumer.profile.account')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_error(): void
    {
        Livewire::test(Account::class)
            ->set('form.first_name', '')
            ->call('updateProfile')
            ->assertHasErrors(['form.first_name' => ['required']])
            ->assertHasNoErrors('form.address', 'form.state', 'form.city', 'form.zip')
            ->assertOk();
    }

    #[Test]
    public function it_can_only_allow_the_defined_states(): void
    {
        Livewire::test(Account::class)
            ->set('form.state', fake()->city())
            ->call('updateProfile')
            ->assertHasErrors(['form.state' => ['in']])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_profile(): void
    {
        Livewire::test(Account::class)
            ->assertSet('form.first_name', $this->consumer->consumerProfile->first_name)
            ->assertSet('form.last_name', $this->consumer->last_name)
            ->assertSet('form.dob', $this->consumer->dob->format('F j, Y'))
            ->assertSet('form.last_four_ssn', $this->consumer->last4ssn)
            ->assertSet('form.address', $this->consumer->consumerProfile->address)
            ->assertSet('form.city', $this->consumer->consumerProfile->city)
            ->assertSet('form.state', $this->consumer->consumerProfile->state)
            ->assertSet('form.zip', $this->consumer->consumerProfile->zip)
            ->set('form.first_name', $firstName = fake()->firstName())
            ->set('form.address', $address = fake()->streetAddress())
            ->set('form.city', $city = fake()->city())
            ->set('form.state', $state = fake()->randomElement(State::values()))
            ->set('form.zip', $zip = fake()->randomNumber(5, true))
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertEquals($firstName, $this->consumer->consumerProfile->refresh()->first_name);
        $this->assertEquals($address, $this->consumer->consumerProfile->address);
        $this->assertEquals($city, $this->consumer->consumerProfile->city);
        $this->assertEquals($state, $this->consumer->consumerProfile->state);
        $this->assertEquals($zip, $this->consumer->consumerProfile->zip);
    }
}
