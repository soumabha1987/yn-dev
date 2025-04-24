<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\ScheduleExport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Enums\ScheduleExportDeliveryType;
use App\Enums\ScheduleExportFrequency;
use App\Livewire\Creditor\Reports\ScheduleExport\CreatePage;
use App\Models\Company;
use App\Models\CsvHeader;
use App\Models\MembershipPaymentProfile;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use App\Models\User;
use App\Rules\MultipleEmails;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        MembershipPaymentProfile::factory()->create(['company_id' => $this->user->company_id]);
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('schedule-export.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function superadmin_can_render_the_livewire_component_with_correct_view(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.create-page')
            ->assertViewHas('clients', fn (array $clients) => $clients[$this->user->company_id] === $this->user->company->company_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_check_allow_report_types(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->assertViewIs('livewire.creditor.reports.schedule-export.create-page')
            ->assertViewHas('reportTypes', fn (array $reportTypes) => Arr::only($reportTypes, NewReportType::displaySelectionBox()) === [])
            ->assertViewHas('clients', fn (array $clients) => $clients[$this->user->company_id] === $this->user->company->company_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_for_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.delivery_type', '')
            ->call('create')
            ->assertHasErrors([
                'form.report_type' => ['required'],
                'form.frequency' => ['required'],
                'form.delivery_type' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_if_delivery_type_is_sftp(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', fake()->randomElement(ScheduleExportFrequency::values()))
            ->set('form.delivery_type', ScheduleExportDeliveryType::SFTP)
            ->call('create')
            ->assertHasErrors([
                'form.sftp_connection_id' => ['required'],
            ])
            ->assertHasNoErrors('form.email')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_if_delivery_type_is_email(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', fake()->randomElement(ScheduleExportFrequency::values()))
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->call('create')
            ->assertHasErrors(['form.emails' => ['required']])
            ->assertHasNoErrors('form.sftp_connection_id')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_of_valid_email(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', fake()->randomElement(ScheduleExportFrequency::values()))
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.emails', fake()->name())
            ->call('create')
            ->assertHasErrors(['form.emails' => [MultipleEmails::class]])
            ->assertHasNoErrors('form.sftp_connection_id')
            ->assertOk();
    }

    #[Test]
    public function superadmin_can_create_schedule_export_with_delivery_type_email(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $reportType = fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', ScheduleExportFrequency::DAILY)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.emails', fake()->safeEmail())
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('schedule-export'));

        Notification::assertNotified(__('Report Scheduled.'));

        $this->assertDatabaseHas(ScheduleExport::class, [
            'user_id' => $this->user->id,
            'company_id' => null,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'report_type' => $reportType,
            'frequency' => ScheduleExportFrequency::DAILY->value,
        ]);
    }

    #[Test]
    public function it_can_create_schedule_export_with_delivery_type_sftp(): void
    {
        $sftpConnection = SftpConnection::factory()->create(['enabled' => true]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.company_id', $this->user->company_id)
            ->set('form.sftp_connection_id', $sftpConnection->id)
            ->set('form.report_type', $reportType = fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', ScheduleExportFrequency::MONTHLY)
            ->set('form.delivery_type', ScheduleExportDeliveryType::SFTP)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('creditor.schedule-export'));

        Notification::assertNotified(__('Report Scheduled.'));

        $scheduleExport = ScheduleExport::query()->firstOrFail();

        $this->assertEquals($this->user->id, $scheduleExport->user_id);
        $this->assertEquals($this->user->company_id, $scheduleExport->company_id);
        $this->assertEquals(null, $scheduleExport->subclient_id);
        $this->assertEquals($reportType, $scheduleExport->report_type->value);
        $this->assertEquals(ScheduleExportFrequency::MONTHLY, $scheduleExport->frequency);
        $this->assertFalse($scheduleExport->pause);
        $this->assertEquals($sftpConnection->id, $scheduleExport->sftp_connection_id);
    }

    #[Test]
    public function it_can_only_allow_five_email_ids(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $email = collect(range(1, fake()->numberBetween(6, 10)))
            ->map(fn () => fake()->email())
            ->implode(fake()->randomElement([',', ', ']));

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.frequency', ScheduleExportFrequency::DAILY)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.report_type', fake()->randomElement(NewReportType::values()))
            ->set('form.emails', $email)
            ->call('create')
            ->assertHasErrors(['form.emails' => [MultipleEmails::class]])
            ->assertHasNoErrors('form.sftp_connection_id')
            ->assertOk();
    }

    #[Test]
    public function it_can_check_record_is_already_created_and_same_record_for_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.emails', 'joe@gmail.com, john@gmail.com, superadmin@yahoo.com')
            ->call('create')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('creditor.schedule-export.edit', $scheduleExport->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );

        $this->assertDatabaseCount(ScheduleExport::class, 1);
    }

    #[Test]
    public function it_can_throw_validation_of_already_created_and_same_record_with_header_and_subclient_when_role_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'sftp_connection_id' => null,
            'emails' => ['jean@gmail.com', 'martin@gmail.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.csv_header_id', $scheduleExport->csv_header_id)
            ->set('form.subclient_id', $this->user->subclient_id)
            ->set('form.emails', 'joe@gmail.com,john@gmail.com,superadmin@yahoo.com,smith@chrome.com')
            ->call('create')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('creditor.schedule-export.edit', $scheduleExport->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );
    }

    #[Test]
    public function it_can_check_record_is_already_created_and_same_record_for_super_admin(): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $user->id,
            'company_id' => null,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => [],
        ]);

        Livewire::actingAs($user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.emails', 'joe@gmail.com, john@gmail.com, superadmin@yahoo.com')
            ->call('create')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('schedule-export.edit', $scheduleExport->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );

        $this->assertDatabaseCount(ScheduleExport::class, 1);
    }

    #[Test]
    public function it_can_throw_validation_of_already_created_and_same_record_with_company_id_when_role_super_admin(): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => ['jean@gmail.com', 'martin@gmail.com'],
        ]);

        Livewire::actingAs($user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.company_id', $this->user->company_id)
            ->set('form.emails', 'joe@gmail.com,john@gmail.com,superadmin@yahoo.com,smith@chrome.com')
            ->call('create')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('schedule-export.edit', $scheduleExport->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );
    }

    #[Test]
    public function it_can_same_email_with_different_companies(): void
    {
        $scheduleExport = ScheduleExport::factory()->create([
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => ['a@a.com', 'b@b.com', 'c@c.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.emails', 'a@a.com, b@b.com, c@c.com, d@d.com, e@e.com, a@a.com')
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseCount(ScheduleExport::class, 2);

        $this->assertNotEquals(['a@a.com', 'b@b.com', 'c@c.com', 'd@d.com', 'e@e.com'], $scheduleExport->refresh()->emails);
    }

    #[Test]
    public function it_can_check_record_is_already_created_and_same_record_with_sftp_connection_id_for_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()
            ->for(SftpConnection::factory()->create(['company_id' => $this->user->company_id]))
            ->for(CsvHeader::factory()->create(['company_id' => $this->user->company_id]))
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'emails' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::SFTP)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.sftp_connection_id', $scheduleExport->sftp_connection_id)
            ->set('form.csv_header_id', $scheduleExport->csv_header_id)
            ->call('create')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                            route('creditor.schedule-export.edit', $scheduleExport->id) .
                            "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );

        $this->assertDatabaseCount(ScheduleExport::class, 1);
    }

    #[Test]
    public function it_can_create_schedule_export_with_delivery_type_sftp_same_report_exists_of_email_delivery_type(): void
    {
        $sftpConnection = SftpConnection::factory()->create(['enabled' => true]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'subclient_id' => $this->user->subclient_id,
                'sftp_connection_id' => null,
                'emails' => ['jean@gmail.com', 'martin@gmail.com'],
            ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.sftp_connection_id', $sftpConnection->id)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.delivery_type', ScheduleExportDeliveryType::SFTP)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('creditor.schedule-export'));

        Notification::assertNotified(__('Report Scheduled.'));

        $this->assertDatabaseCount(ScheduleExport::class, 2);

        $this->assertDatabaseHas(ScheduleExport::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'sftp_connection_id' => $sftpConnection->id,
            'report_type' => $scheduleExport->report_type,
            'frequency' => $scheduleExport->frequency,
        ]);
    }

    #[Test]
    public function it_can_create_creditor_which_is_same_record_existst_on_super_admin(): void
    {
        $user = User::factory()
            ->for(Company::factory()->create(['is_super_admin_company' => true]))
            ->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $this->user->assignRole(Role::query()->create(['name' => EnumRole::CREDITOR]));

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'emails' => ['jean@gmail.com', 'martin@gmail.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(CreatePage::class)
            ->set('form.report_type', $scheduleExport->report_type)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', $scheduleExport->frequency)
            ->set('form.emails', 'jean@gmail.com,martin@gmail.com')
            ->call('create')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDontSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('schedule-export.edit', $scheduleExport->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );

        $this->assertDatabaseCount(ScheduleExport::class, 2);

        $this->assertDatabaseHas(ScheduleExport::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'sftp_connection_id' => null,
            'csv_header_id' => null,
            'report_type' => $scheduleExport->report_type,
            'frequency' => $scheduleExport->frequency,
        ]);
    }
}
