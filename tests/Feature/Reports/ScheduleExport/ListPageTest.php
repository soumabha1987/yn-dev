<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\ScheduleExport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Enums\ScheduleExportFrequency;
use App\Livewire\Creditor\Reports\ScheduleExport\ListPage;
use App\Models\Company;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
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
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('schedule-export'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_correct_view_on_livewire_component_with_empty_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.list-page')
            ->assertViewHas('scheduleExports', fn (LengthAwarePaginator $scheduleExports) => $scheduleExports->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()
            ->for(Subclient::factory()->create(['subclient_name' => fake()->lastName()]))
            ->create([
                'report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE,
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.list-page')
            ->assertViewHas('scheduleExports', fn (LengthAwarePaginator $scheduleExports) => $scheduleExport->is($scheduleExports->getCollection()->first()))
            ->assertSee(Str::limit($scheduleExport->subclient->subclient_name, 12))
            ->assertDontSee(__('All'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data_when_role_super_admin(): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $scheduleExport = ScheduleExport::factory()->create([
            'report_type' => NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY,
            'user_id' => $user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.list-page')
            ->assertViewHas('scheduleExports', fn (LengthAwarePaginator $scheduleExports) => $scheduleExport->is($scheduleExports->getCollection()->first()))
            ->assertSee(Str::limit($scheduleExport->company->company_name, 12))
            ->assertDontSee(__('All'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_for_all_subclients_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.list-page')
            ->assertViewHas('scheduleExports', fn (LengthAwarePaginator $scheduleExports) => $scheduleExport->is($scheduleExports->getCollection()->first()))
            ->assertSee(__('All'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_for_all_creditor_role_super_admin(): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $user->id,
            'company_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.list-page')
            ->assertViewHas('scheduleExports', fn (LengthAwarePaginator $scheduleExports) => $scheduleExport->is($scheduleExports->getCollection()->first()))
            ->assertSee(__('All'))
            ->assertOk();
    }

    #[Test]
    public function toggle_pause_schedule_export(): void
    {
        $method = ($pause = fake()->boolean()) ? 'assertFalse' : 'assertTrue';

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'pause' => $pause,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('togglePause', $scheduleExport)
            ->assertOk();

        $this->{$method}($scheduleExport->pause);
    }

    #[Test]
    public function it_can_delete_schedule_export(): void
    {
        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $scheduleExport)
            ->assertOk();

        $this->assertModelMissing($scheduleExport);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_created_on(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $createdScheduleExports = ScheduleExport::factory(13)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
        ]);

        $createdScheduleExports->each(function (ScheduleExport $scheduleExport, int $index): void {
            $scheduleExport->forceFill(['created_at' => now()->addDays($index)]);
            $scheduleExport->save();
        });

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'created_on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_type(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $createdScheduleExports = ScheduleExport::factory(5)
            ->sequence(
                ['report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY],
                ['report_type' => NewReportType::CONSUMER_PAYMENTS],
                ['report_type' => NewReportType::DISPUTE_NO_PAY],
                ['report_type' => NewReportType::FINAL_PAYMENTS_BALANCE_SUMMARY],
                ['report_type' => NewReportType::SUMMARY_BALANCE_COMPLIANCE],
            )
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams([
            'sort' => 'type',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_frequency(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $createdScheduleExports = ScheduleExport::factory(3)
            ->sequence(
                ['frequency' => ScheduleExportFrequency::DAILY],
                ['frequency' => ScheduleExportFrequency::MONTHLY],
                ['frequency' => ScheduleExportFrequency::WEEKLY]
            )
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams([
            'sort' => 'frequency',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'frequency')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_client_name(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $createdScheduleExports = ScheduleExport::factory(13)
            ->sequence(fn (Sequence $sequence) => ['subclient_id' => Subclient::factory()->state(['subclient_name' => range('A', 'Z')[$sequence->index]])])
            ->create([
                'user_id' => $this->user->id,
            ]);

        Livewire::withQueryParams([
            'sort' => 'client_name',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'client_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_client_name_for_super_admin(string $direction): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $createdScheduleExports = ScheduleExport::factory(13)
            ->sequence(fn (Sequence $sequence) => ['company_id' => Company::factory()->state(['company_name' => range('A', 'Z')[$sequence->index]])])
            ->create([
                'user_id' => $user->id,
            ]);

        Livewire::withQueryParams([
            'sort' => 'client_name',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'client_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_delivery_type(string $direction): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $createdScheduleExports = ScheduleExport::factory()
            ->forEachSequence(
                ['sftp_connection_id' => null],
                ['sftp_connection_id' => SftpConnection::factory()->state(['name' => fake()->name()])],
            )
            ->create([
                'emails' => collect()->times(fake()->numberBetween(1, 5), fn () => fake()->unique()->email())->all(),
                'user_id' => $this->user->id,
            ]);

        Livewire::withQueryParams([
            'sort' => 'delivery_type',
            'direction' => $direction === 'ASC',
        ])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'delivery_type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'scheduleExports',
                fn (LengthAwarePaginator $scheduleExports) => $direction === 'ASC'
                    ? $createdScheduleExports->first()->is($scheduleExports->getCollection()->first())
                    : $createdScheduleExports->last()->is($scheduleExports->getCollection()->first())
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
