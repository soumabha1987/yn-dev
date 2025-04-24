<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard\Stats;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Dashboard\Stats\PaymentForecastPage;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentForecastPageTest extends TestCase
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
            ->get(route('creditor.dashboard.payment-forecast'))
            ->assertSeeLivewire(PaymentForecastPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.payment-forecast-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransactions->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'company_id' => $this->user->company_id,
        ]);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
            'status' => TransactionStatus::SCHEDULED->value,
            'schedule_date' => now()->addDays(5)->toDateString(),
        ]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.payment-forecast-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransaction->is($scheduleTransactions->getCollection()->first()))
            ->assertSee(Carbon::parse($scheduleTransaction->schedule_date)->format('M d, Y'))
            ->assertSee($scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
        ]);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'consumer_id' => $consumer->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'status' => TransactionStatus::SCHEDULED->value,
            'schedule_date' => now()->addDays(5)->toDateString(),
        ]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.payment-forecast-page')
            ->assertViewHas('scheduleTransactions', fn (LengthAwarePaginator $scheduleTransactions) => $scheduleTransaction->is($scheduleTransactions->getCollection()->first()))
            ->assertSee($scheduleTransaction->schedule_date->format('M d, Y'))
            ->assertSee($scheduleTransaction->consumer->first_name . ' ' . $scheduleTransaction->consumer->last_name)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_date_time(string $direction): void
    {
        $consumer = Consumer::factory()
            ->for($this->user->company)
            ->create(['status' => ConsumerStatus::JOINED]);

        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => ['schedule_date' => now()->addDays($sequence->index)])
            ->for($this->user->company)
            ->for($consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'date_time')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_transaction_amount(string $direction): void
    {
        $consumer = Consumer::factory()
            ->for($this->user->company)
            ->create(['status' => ConsumerStatus::JOINED]);

        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => ['amount' => $sequence->index + 32])
            ->for($this->user->company)
            ->for($consumer)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'schedule_date' => now()->addDays(2),
            ]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertOk()
            ->set('sortCol', 'transaction_amount')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'status' => ConsumerStatus::JOINED,
                        'first_name' => null,
                        'last_name' => range('A', 'Z')[$sequence->index],
                    ]),
            ])
            ->for($this->user->company)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'schedule_date' => now()->addDays(2),
            ]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_member_account_number(string $direction): void
    {
        $createdScheduleTransactions = ScheduleTransaction::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'consumer_id' => Consumer::factory()
                    ->for($this->user->company)
                    ->state([
                        'status' => ConsumerStatus::JOINED,
                        'member_account_number' => $sequence->index,
                    ]),
            ])
            ->for($this->user->company)
            ->create([
                'status' => TransactionStatus::SCHEDULED,
                'schedule_date' => now()->addDays(2),
            ]);

        Livewire::actingAs($this->user)
            ->test(PaymentForecastPage::class)
            ->assertOk()
            ->set('sortCol', 'member_account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleTransactions',
                fn (LengthAwarePaginator $scheduleTransactions) => $direction === 'ASC'
                    ? $createdScheduleTransactions->first()->is($scheduleTransactions->getCollection()->first())
                    : $createdScheduleTransactions->last()->is($scheduleTransactions->getCollection()->first())
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
