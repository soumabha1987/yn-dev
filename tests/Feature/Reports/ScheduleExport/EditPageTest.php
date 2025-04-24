<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\ScheduleExport;

use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Enums\ScheduleExportDeliveryType;
use App\Enums\ScheduleExportFrequency;
use App\Livewire\Creditor\Reports\ScheduleExport\EditPage;
use App\Models\MembershipPaymentProfile;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditPageTest extends TestCase
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

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('schedule-export.edit', ['scheduleExport' => $scheduleExport->id]))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function superadmin_can_render_the_livewire_component_with_correct_view(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExport])
            ->assertViewIs('livewire.creditor.reports.schedule-export.edit-page')
            ->assertViewHas('clients', fn (array $clients) => $clients[$this->user->company_id] === $this->user->company->company_name)
            ->assertSet('form.report_type', $scheduleExport->report_type->value)
            ->assertSet('form.frequency', $scheduleExport->frequency->value)
            ->assertSet('form.delivery_type', $deliveryType = $scheduleExport->sftp_connection_id ? ScheduleExportDeliveryType::SFTP->value : ScheduleExportDeliveryType::EMAIL->value)
            ->assertSet('form.company_id', $scheduleExport->company_id ?? '')
            ->tap(function (Testable $test) use ($deliveryType, $scheduleExport): void {
                if ($deliveryType === ScheduleExportDeliveryType::EMAIL->value) {
                    $test->assertSet('form.emails', implode(', ', $scheduleExport->emails))
                        ->assertSet('form.sftp_connection_id', '');
                } else {
                    $test->assertSet('form.emails', '')
                        ->assertSet('form.sftp_connection_id', $scheduleExport->sftp_connection_id);
                }
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_for_required(): void
    {
        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExport])
            ->set('form.report_type', '')
            ->set('form.frequency', '')
            ->call('update')
            ->assertHasErrors([
                'form.report_type' => ['required'],
                'form.frequency' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_if_delivery_type_is_email(): void
    {
        $scheduleExport = ScheduleExport::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'sftp_connection_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExport])
            ->set('form.report_type', fake()->randomElement(NewReportType::values()))
            ->set('form.frequency', ScheduleExportFrequency::DAILY)
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.emails', '')
            ->call('update')
            ->assertHasErrors(['form.emails' => ['required']])
            ->assertHasNoErrors('form.sftp_connection_id')
            ->assertOk();
    }

    #[Test]
    public function creditor_update_email_to_sftp(): void
    {
        $scheduleExport = ScheduleExport::factory()
            ->create([
                'sftp_connection_id' => null,
                'emails' => ['test@gmail.com', 'test1@gmail.com'],
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        $sftpConnection = SftpConnection::factory()->create(['enabled' => true]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExport])
            ->assertSet('form.emails', implode(', ', $scheduleExport->emails))
            ->set('form.delivery_type', ScheduleExportDeliveryType::SFTP)
            ->set('form.sftp_connection_id', $sftpConnection->id)
            ->set('form.report_type', $reportType = NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.frequency', $frequency = ScheduleExportFrequency::DAILY)
            ->call('update')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertNull($scheduleExport->refresh()->emails);

        $this->assertEquals($reportType, $scheduleExport->report_type);
        $this->assertEquals($frequency, $scheduleExport->frequency);
        $this->assertEquals($this->user->id, $scheduleExport->user_id);
        $this->assertEquals($sftpConnection->id, $scheduleExport->sftp_connection_id);
    }

    #[Test]
    public function creditor_update_sftp_to_email(): void
    {
        $scheduleExport = ScheduleExport::factory()
            ->for(SftpConnection::factory()->state(['enabled' => true]))
            ->create([
                'emails' => null,
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExport])
            ->assertSet('form.sftp_connection_id', $scheduleExport->sftp_connection_id)
            ->assertSet('form.emails', '')
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.frequency', ScheduleExportFrequency::MONTHLY)
            ->set('form.emails', $email = fake()->safeEmail())
            ->call('update')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertEquals([$email], $scheduleExport->refresh()->emails);

        $this->assertNull($scheduleExport->sftp_connection_id);
        $this->assertNull($scheduleExport->weekly);
        $this->assertEquals($scheduleExport->user_id, $this->user->id);
    }

    #[Test]
    public function it_can_update_schedule_report_but_same_record_already_exists(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $scheduleExports = ScheduleExport::factory()
            ->forEachSequence(
                ['frequency' => ScheduleExportFrequency::MONTHLY],
                ['frequency' => ScheduleExportFrequency::DAILY],
            )
            ->create([
                'sftp_connection_id' => null,
                'emails' => ['test@gmail.com', 'test1@gmail.com'],
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'report_type' => NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY->value,
                'csv_header_id' => null,
            ]);

        Livewire::actingAs($this->user)
            ->test(EditPage::class, ['scheduleExport' => $scheduleExports->first()])
            ->assertSet('form.emails', 'test@gmail.com, test1@gmail.com')
            ->set('form.delivery_type', ScheduleExportDeliveryType::EMAIL)
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY->value)
            ->set('form.frequency', ScheduleExportFrequency::DAILY)
            ->set('form.csv_header_id', '')
            ->set('form.emails', $email = fake()->safeEmail())
            ->call('update')
            ->assertOk()
            ->assertSeeHtml(
                __(
                    'Sorry this schedule report already exists :url',
                    ['url' => "<a href='" .
                        route('creditor.schedule-export.edit', $scheduleExports->last()->id) .
                        "' class='font-bold'>click here to edit</a>",
                    ]
                )
            );

        $this->assertNotEquals([$email], $scheduleExports->first()->refresh()->emails);
    }
}
