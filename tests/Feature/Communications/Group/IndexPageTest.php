<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\Group;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\GroupConsumerState;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Communications\Group\IndexPage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Group;
use App\Models\Merchant;
use App\Models\Reason;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user->update(['subclient_id' => null]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::TERMS_AND_CONDITIONS],
                ['type' => CustomContentType::ABOUT_US]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()
            ->create([
                'subclient_id' => null,
                'company_id' => $this->user->company_id,
                'is_mapped' => true,
            ]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('creditor.communication.groups'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_not_completed_setup_wizard(): void
    {
        $company = Company::factory()->create();

        $this->user->update(['company_id' => $company->id]);

        $this->get(route('creditor.communication.groups'))
            ->assertDontSeeLivewire(IndexPage::class)
            ->assertRedirectToRoute('creditor.profile')
            ->assertStatus(302);
    }

    #[Test]
    public function it_can_ignore_completed_setup_wizard_when_role_super_admin(): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $this->actingAs($user)
            ->get(route('super-admin.communication.groups'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_livewire_campaign_view_page(): void
    {
        Livewire::test(IndexPage::class)
            ->assertViewIs('livewire.creditor.communications.group.index-page')
            ->assertSee(__('Preview Group Size'))
            ->assertSee(__('Save'))
            ->assertOk();
    }

    #[Test]
    public function it_can_create_the_group_when_consumer_state_is_all_active(): void
    {
        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::ALL_ACTIVE)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertDispatched('refresh-parent')
            ->assertSet('form.name', '')
            ->assertSet('form.consumer_state', '')
            ->assertSet('form.custom_rules', []);

        Notification::assertNotified(__('Group created.'));

        $this->assertDatabaseHas(Group::class, [
            'name' => 'Test group name',
            'consumer_state' => GroupConsumerState::ALL_ACTIVE,
            'custom_rules' => null,
        ]);
    }

    #[Test]
    public function it_can_preview_the_group_size_when_consumer_state_is_not_viewed_offer(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => fake()->randomElement(ConsumerStatus::notVerified()),
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::NOT_VIEWED_OFFER)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_preview_the_group_size_when_consumer_state_is_viewed_offer_but_no_response(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::JOINED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::VIEWED_OFFER_BUT_NO_RESPONSE)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_preview_the_group_size_when_consumer_state_is_open_negotiations(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::OPEN_NEGOTIATIONS)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_preview_the_group_when_consumer_state_is_not_responded_to_counter_offer(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'counter_offer' => true,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::NOT_RESPONDED_TO_COUNTER_OFFER)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_preview_the_group_size_when_consumer_state_is_negotiated_payoff_but_pending_payment(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->has(ConsumerNegotiation::factory()->state([
                'company_id' => $this->company->id,
                'negotiation_type' => NegotiationType::PIF,
            ]))
            ->create([
                'subclient_id' => null,
                'payment_setup' => true,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::NEGOTIATED_PAYOFF_BUT_PENDING_PAYMENT)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_preview_the_group_size_when_consumer_state_is_negotiated_plan_but_pending_payment(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->has(ConsumerNegotiation::factory()->state([
                'company_id' => $this->company->id,
                'negotiation_type' => NegotiationType::INSTALLMENT,
            ]))
            ->create([
                'subclient_id' => null,
                'payment_setup' => true,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::NEGOTIATED_PLAN_BUT_PENDING_PAYMENT)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_calculate_the_group_size_when_consumer_state_is_failed_or_skip_more_than_two_payments_consecutively(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->has(
                ScheduleTransaction::factory(2)
                    ->for($this->company)
                    ->state([
                        'subclient_id' => null,
                        'previous_schedule_date' => fake()->boolean() ? now() : null,
                        'status' => TransactionStatus::FAILED,
                    ]),
                'scheduledTransactions'
            )
            ->create([
                'subclient_id' => null,
                'payment_setup' => true,
                'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::FAILED_OR_SKIP_MORE_THAN_TWO_PAYMENTS_CONSECUTIVELY)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_create_the_group_when_consumer_state_is_reported_not_paying(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->for(Reason::factory())
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::DEACTIVATED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::REPORTED_NOT_PAYING)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_create_the_group_when_consumer_state_is_disputed(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'disputed_at' => now(),
                'status' => ConsumerStatus::DEACTIVATED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::DISPUTED)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_create_the_group_when_consumer_state_is_deactivated(): void
    {
        Consumer::factory(3)
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::DEACTIVATED,
                'current_balance' => '23',
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.name', 'Test group name')
            ->set('form.consumer_state', GroupConsumerState::DEACTIVATED)
            ->assertSet('openModal', false)
            ->assertSet('groupSize', null)
            ->assertSet('totalBalance', '')
            ->call('calculateGroupSize')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true);
    }

    #[Test]
    public function it_can_edit_group(): void
    {
        $group = Group::factory()
            ->for($this->user)
            ->for($this->company)
            ->create();

        Livewire::test(IndexPage::class)
            ->call('edit', $group->id)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.name', $group->name)
            ->assertSet('form.consumer_state', $group->consumer_state->value)
            ->assertSet('form.custom_rules', [])
            ->assertDispatched('update-custom-rules')
            ->assertDispatched('close-menu');
    }
}
