<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomatedCommunicationHistory;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedCommunicationHistory\ListPage;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component_of_the_communication_history_page(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.automated-communication-histories'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_correct_view(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automated-communication-history.list-page')
            ->assertViewHas('automatedCommunicationHistories', fn (LengthAwarePaginator $automatedCommunicationHistories) => $automatedCommunicationHistories->isEmpty())
            ->assertViewHas('companies', fn (array $companies) => $companies === [])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_deleted_company_communication_history_display(): void
    {
        $automatedCommunicationHistory = AutomatedCommunicationHistory::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $automatedCommunicationHistories->getCollection()->doesntContain($automatedCommunicationHistory)
            )
            ->assertViewHas('companies', fn (array $companies) => $companies === [])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data(): void
    {
        $communicationStatus = CommunicationStatus::factory()->create([
            'code' => CommunicationCode::WELCOME,
            'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
        ]);

        $automatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(
                ['cost' => 42, 'status' => AutomatedCommunicationHistoryStatus::SUCCESS],
                ['cost' => 23, 'status' => AutomatedCommunicationHistoryStatus::SUCCESS],
                ['cost' => 89, 'status' => AutomatedCommunicationHistoryStatus::FAILED]
            )
            ->for($communicationStatus)
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewHas('automatedCommunicationHistories', fn (LengthAwarePaginator $automatedCommunicationHistories) => $automatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first()))
            ->assertViewHas('companies', fn (array $companies) => count($companies) === 3)
            ->assertSet('totalCost', 154)
            ->tap(function (Testable $test): void {
                $test->set('status', AutomatedCommunicationHistoryStatus::SUCCESS)
                    ->assertSet('totalCost', 65);
            })
            ->tap(function (Testable $test): void {
                $test->set('status', AutomatedCommunicationHistoryStatus::FAILED)
                    ->assertSet('totalCost', 89);
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_filter_by_subclient_when_company_is_changed_at_that_time_filter_is_reset(): void
    {
        $automatedCommunicationHistories = AutomatedCommunicationHistory::factory()
            ->for(Subclient::factory())
            ->create();

        Livewire::test(ListPage::class)
            ->tap(function (Testable $test): void {
                $test->set('subclient', fake()->randomDigitNotZero())
                    ->assertViewHas('automatedCommunicationHistories', fn (LengthAwarePaginator $automatedCommunicationHistories) => $automatedCommunicationHistories->isEmpty());
            })
            ->set('subclient', fake()->randomDigitNotZero())
            ->set('company', $automatedCommunicationHistories->company_id)
            ->assertViewHas('automatedCommunicationHistories', fn (LengthAwarePaginator $automatedCommunicationHistories) => $automatedCommunicationHistories->count() === 1)
            ->assertSet('subclient', '');
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_communication_code(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(
                [
                    'communication_status_id' => CommunicationStatus::factory()
                        ->state([
                            'code' => CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE,
                            'automated_email_template_id' => null,
                            'automated_sms_template_id' => null,
                        ]),
                ],
                [
                    'communication_status_id' => CommunicationStatus::factory()
                        ->state([
                            'code' => CommunicationCode::BALANCE_PAID,
                            'automated_email_template_id' => null,
                            'automated_sms_template_id' => null,
                        ]),
                ],
                [
                    'communication_status_id' => CommunicationStatus::factory()
                        ->state([
                            'code' => CommunicationCode::PAYMENT_FAILED_WHEN_INSTALLMENT,
                            'automated_email_template_id' => null,
                            'automated_sms_template_id' => null,
                        ]),
                ],
            )
            ->create();

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'code')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_name(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(fn (Sequence $sequence) => ['company_id' => Company::factory()->state(['company_name' => range('A', 'Z')[$sequence->index]])])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'company_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'company_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(
                ['consumer_id' => Consumer::factory()->state(['first_name' => '', 'last_name' => 'Anderson'])],
                ['consumer_id' => Consumer::factory()->state(['first_name' => null, 'last_name' => 'Bachan'])],
                ['consumer_id' => Consumer::factory()->state(['first_name' => 'Caleb', 'last_name' => 'Porzio'])],
            )
            ->create();

        Livewire::withQueryParams([
            'sort' => 'consumer_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'consumer_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_automated_template_type(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(2)
            ->sequence(
                ['automated_template_type' => AutomatedTemplateType::EMAIL],
                ['automated_template_type' => AutomatedTemplateType::SMS]
            )
            ->create();

        Livewire::withQueryParams([
            'sort' => 'template_type',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'template_type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_cost(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(
                ['cost' => 10.22],
                ['cost' => 12.45],
                ['cost' => 14.22],
            )
            ->create();

        Livewire::withQueryParams([
            'sort' => 'cost',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'cost')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_automated_template_name(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(fn (Sequence $sequence) => ['automated_template_id' => AutomatedTemplate::factory()->state(['name' => range('A', 'Z')[$sequence->index]])])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'template_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'template_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $createdAutomatedCommunicationHistories->first()->is($automatedCommunicationHistories->getCollection()->first())
                    : $createdAutomatedCommunicationHistories->last()->is($automatedCommunicationHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_automated_status(string $direction): void
    {
        $createdAutomatedCommunicationHistories = AutomatedCommunicationHistory::factory(3)
            ->sequence(
                ['status' => AutomatedCommunicationHistoryStatus::FAILED],
                ['status' => AutomatedCommunicationHistoryStatus::IN_PROGRESS],
                ['status' => AutomatedCommunicationHistoryStatus::SUCCESS]
            )
            ->create();

        Livewire::withQueryParams([
            'sort' => 'status',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'status')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedCommunicationHistories',
                fn (LengthAwarePaginator $automatedCommunicationHistories) => $direction === 'ASC'
                    ? $automatedCommunicationHistories->getCollection()->first()->status === AutomatedCommunicationHistoryStatus::FAILED
                    : $automatedCommunicationHistories->getCollection()->first()->status === AutomatedCommunicationHistoryStatus::SUCCESS
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
