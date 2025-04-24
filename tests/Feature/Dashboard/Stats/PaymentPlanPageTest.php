<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard\Stats;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Dashboard\Stats\PaymentPlanPage;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\PaymentProfile;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentPlanPageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $this->user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        CompanyMembership::factory()
            ->for($this->user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE->value,
                'current_plan_end' => now()->addMonth(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('creditor.dashboard.payment-plan'))
            ->assertSeeLivewire(PaymentPlanPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.payment-plan-page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'offer_accepted' => true,
            'payment_setup' => true,
        ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
            'negotiation_type' => NegotiationType::PIF->value,
            'offer_accepted' => true,
        ]);

        PaymentProfile::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->consumerNegotiation->offer_accepted)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'offer_accepted' => true,
            'payment_setup' => true,
        ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
            'negotiation_type' => NegotiationType::PIF->value,
            'offer_accepted' => true,
        ]);

        PaymentProfile::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->consumerNegotiation->offer_accepted)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => [
                'first_name' => null,
                'last_name' => range('A', 'Z')[$sequence->index],
            ])
            ->has(
                ConsumerNegotiation::factory()
                    ->state([
                        'company_id' => $this->user->company_id,
                        'negotiation_type' => NegotiationType::PIF,
                        'offer_accepted' => true,
                    ])
            )
            ->has(PaymentProfile::factory()->state(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'offer_accepted' => true,
                'payment_setup' => true,
            ]);

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'consumer_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_member_account_number(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => ['member_account_number' => $sequence->index])
            ->has(
                ConsumerNegotiation::factory()
                    ->state([
                        'company_id' => $this->user->company_id,
                        'negotiation_type' => NegotiationType::PIF,
                        'offer_accepted' => true,
                    ])
            )
            ->has(PaymentProfile::factory()->state(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'offer_accepted' => true,
                'payment_setup' => true,
            ]);

        Livewire::withQueryParams([
            'sort' => 'member_account_number',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'member_account_number')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sub_account(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => ['subclient_name' => range('A', 'Z')[$sequence->index]])
            ->has(
                ConsumerNegotiation::factory()
                    ->state([
                        'company_id' => $this->user->company_id,
                        'negotiation_type' => NegotiationType::PIF,
                        'offer_accepted' => true,
                    ])
            )
            ->has(PaymentProfile::factory()->state(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'offer_accepted' => true,
                'payment_setup' => true,
            ]);

        Livewire::withQueryParams([
            'sort' => 'sub_account',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sub_account')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_current_balance(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => ['current_balance' => $sequence->index + 35.44])
            ->has(
                ConsumerNegotiation::factory()
                    ->state([
                        'company_id' => $this->user->company_id,
                        'negotiation_type' => NegotiationType::PIF,
                        'offer_accepted' => true,
                    ])
            )
            ->has(PaymentProfile::factory()->state(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'offer_accepted' => true,
                'payment_setup' => true,
            ]);

        Livewire::withQueryParams([
            'sort' => 'current_balance',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'current_balance')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_payment_profile_created_date(string $direction): void
    {
        $createdConsumers = Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => ['current_balance' => $sequence->index + 35.44])
            ->has(
                ConsumerNegotiation::factory()
                    ->state([
                        'company_id' => $this->user->company_id,
                        'negotiation_type' => NegotiationType::PIF,
                        'offer_accepted' => true,
                    ])
            )
            ->has(PaymentProfile::factory()->state(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'offer_accepted' => true,
                'payment_setup' => true,
            ]);

        $createdConsumers->each(function (Consumer $consumer, int $index): void {
            $consumer->paymentProfile->forceFill(['created_at' => now()->addDays($index)]);
            $consumer->paymentProfile->save();
        });

        Livewire::withQueryParams([
            'sort' => 'profile_created_on',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(PaymentPlanPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'profile_created_on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}
