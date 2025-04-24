<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\GenerateReport;

use App\Enums\CompanyStatus;
use App\Enums\ReportType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage as GenerateReportPage;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use App\Models\Subclient;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.generate-report.index-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page_with_data_when_role_super_admin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->company()->update([
            'status' => CompanyStatus::ACTIVE,
            'is_super_admin_company' => true,
        ]);

        $this->user->assignRole($role);

        $reportTypes = collect(ReportType::displaySelectionBox())
            ->except([
                ReportType::CONSUMER_ACTIVITIES->value,
                ReportType::RECENT_TRANSACTIONS->value,
                ReportType::UPCOMING_TRANSACTIONS->value,
            ])
            ->all();

        [$company1, $company2] = Company::factory(2)
            ->create()
            ->each(function (Company $company) {
                MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);
            });

        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.generate-report.index-page')
            ->assertSet('reportTypes', $reportTypes)
            ->assertViewHas(
                'subAccounts',
                fn (array $subAccounts) => Arr::exists($subAccounts, $company1->id)
                    && Arr::exists($subAccounts, $company2->id)
            )
            ->assertSet('reportType', '')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page_with_data_when_role_creditor(): void
    {
        $this->roleCreditor();

        $reportTypes = collect(ReportType::displaySelectionBox())
            ->except([
                ReportType::CONSUMER_ACTIVITIES->value,
                ReportType::RECENT_TRANSACTIONS->value,
                ReportType::UPCOMING_TRANSACTIONS->value,
                ReportType::BILLING_HISTORIES->value,
            ])
            ->all();

        [$subclient1, $subclient2] = Subclient::factory(2)->for($this->user->company)->create();

        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.generate-report.index-page')
            ->assertSet('reportTypes', $reportTypes)
            ->assertViewHas('subAccounts', [
                'master' => 'Master - All accounts',
                $this->subclient->id => $this->subclient->subclient_name . '/' . $this->subclient->unique_identification_number,
                $subclient1->id => $subclient1->subclient_name . '/' . $subclient1->unique_identification_number,
                $subclient2->id => $subclient2->subclient_name . '/' . $subclient2->unique_identification_number,
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_form_required_validation_when_superadmin_role(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->call('generateReport')
            ->assertHasErrors([
                'form.report_type' => ['required'],
                'form.start_date' => ['required'],
                'form.end_date' => ['required'],
            ])
            ->assertHasNoErrors(['form.subclient_id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_form_required_validation_when_role_creditor(): void
    {
        $this->roleCreditor();

        Livewire::test(GenerateReportPage::class)
            ->call('generateReport')
            ->assertHasErrors([
                'form.subclient_id' => ['required'],
                'form.report_type' => ['required'],
                'form.start_date' => ['required'],
                'form.end_date' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_for_report_type(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', fake()->randomElement([
                ReportType::CONSUMER_ACTIVITIES,
                ReportType::RECENT_TRANSACTIONS,
                ReportType::UPCOMING_TRANSACTIONS,
            ]))
            ->call('generateReport')
            ->assertOk()
            ->assertHasErrors(['form.report_type' => ['in']]);
    }

    #[Test]
    public function it_can_render_submit_form_non_required_rule_and_date_validation(): void
    {
        $this->roleCreditor();

        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', fake()->sentence(1))
            ->set('form.subclient_id', fake()->sentence(1))
            ->set('form.start_date', '2023-12-12')
            ->set('form.end_date', fake()->sentence(1))
            ->call('generateReport')
            ->assertHasErrors([
                'form.report_type' => ['in'],
                'form.subclient_id' => ['exists'],
                'form.end_date' => ['date'],
            ])
            ->assertHasNoErrors(['form.start_date'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_transaction_report_type_start_and_end_date_gap_more_then_two_months_validation(): void
    {
        $this->roleCreditor();

        $subclient = Subclient::factory()->for($this->user->company)->create();

        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.subclient_id', $subclient->id)
            ->set('form.start_date', $startDate = fake()->date())
            ->set('form.end_date', Carbon::parse($startDate)->addMonths(3)->toDateString())
            ->call('generateReport')
            ->assertHasErrors(['form.end_date' => ['before_or_equal']])
            ->assertHasNoErrors(['form.start_date', 'form.report_type', 'form.subclient_id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_transaction_report_type_end_date_more_then_today_validation(): void
    {
        $this->roleCreditor();

        $subclient = Subclient::factory()->for($this->user->company)->create();

        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.subclient_id', $subclient->id)
            ->set('form.start_date', $startDate = today()->subMonth()->toDateString())
            ->set('form.end_date', Carbon::parse($startDate)->addMonths(2)->toDateString())
            ->call('generateReport')
            ->assertHasErrors(['form.end_date' => 'before_or_equal:today'])
            ->assertHasNoErrors(['form.start_date', 'form.report_type', 'form.subclient_id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_transaction_report_type_start_and_end_date_is_future_validation(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', $startDate = today()->addMonth())
            ->set('form.end_date', Carbon::parse($startDate)->addMonth()->toDateString())
            ->call('generateReport')
            ->assertHasErrors(['form.start_date' => 'before_or_equal:today', 'form.end_date' => 'before_or_equal:today'])
            ->assertHasNoErrors(['form.report_type', 'form.subclient_id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_schedule_transactions_report_type_start_and_end_date_gap_more_then_two_months_validation(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.subclient_id', $subclient->id)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', Carbon::parse($startDate)->addMonths(3)->toDateString())
            ->call('generateReport')
            ->assertHasErrors(['form.end_date' => ['before_or_equal']])
            ->assertHasNoErrors(['form.start_date', 'form.report_type', 'form.subclient_id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_schedule_transactions_report_type_start_and_end_date_is_future_validation(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = fake()->date())
            ->set('form.end_date', Carbon::parse($startDate)->addMonth()->toDateString())
            ->call('generateReport')
            ->assertHasErrors(['form.start_date' => 'after_or_equal:today'])
            ->assertHasNoErrors(['form.report_type', 'form.subclient_id', 'form.end_date'])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_schedule_transactions_report_type_no_validation_error(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::SCHEDULED_TRANSACTIONS)
            ->set('form.start_date', $startDate = today()->toDateString())
            ->set('form.end_date', Carbon::parse($startDate)->addMonth()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_type_when_transaction_report_type_no_validation_error(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', ReportType::TRANSACTION_HISTORY)
            ->set('form.start_date', $startDate = fake()->date())
            ->set('form.end_date', Carbon::parse($startDate)->addMonth()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();
    }

    private function roleCreditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        $this->user->assignRole($role);
    }
}
