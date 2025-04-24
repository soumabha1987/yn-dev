<?php

declare(strict_types=1);

namespace Tests\Feature\Reports\NewGenerateReport;

use App\Enums\CompanyStatus;
use App\Enums\NewReportType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Reports\NewGenerateReport\IndexPage as GenerateReportPage;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use App\Models\Subclient;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_generate_reports_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        $this->user->assignRole($role);

        $this->get(route('generate-reports'))
            ->assertSeeLivewire(GenerateReportPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.new-generate-report.index-page')
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

        [$company1, $company2] = Company::factory(2)
            ->create()
            ->each(function (Company $company) {
                MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);
            });

        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.new-generate-report.index-page')
            ->assertSet('reportTypes', NewReportType::displaySelectionBox())
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

        $reportTypes = collect(NewReportType::displaySelectionBox())
            ->except(NewReportType::BILLING_HISTORIES->value)
            ->all();

        [$subclient1, $subclient2] = Subclient::factory(2)->for($this->user->company)->create();

        Livewire::test(GenerateReportPage::class)
            ->assertViewIs('livewire.creditor.reports.new-generate-report.index-page')
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
    #[DataProvider('requestValidationRule')]
    public function it_can_render_validation_rule(array $requestData, array $requestError): void
    {
        $this->roleCreditor();

        Livewire::test(GenerateReportPage::class)
            ->set($requestData)
            ->call('generateReport')
            ->assertOk()
            ->assertHasErrors($requestError);
    }

    #[Test]
    public function it_can_render_submit_generate_report_no_validation_error(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::ALL_ACCOUNTS_STATUS_AND_ACTIVITY)
            ->set('form.start_date', today()->subMonth()->toDateString())
            ->set('form.end_date', today()->toDateString())
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertOk();
    }

    #[Test]
    public function it_can_render_submit_generate_with_out_date_no_validation_error(): void
    {
        Livewire::test(GenerateReportPage::class)
            ->set('form.report_type', NewReportType::CONSUMER_OPT_OUT)
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

    public static function requestValidationRule(): array
    {
        return [
            [
                [],
                [
                    'form.subclient_id' => ['required'],
                    'form.report_type' => ['required'],
                    'form.start_date' => ['required'],
                    'form.end_date' => ['required'],
                ],
            ],
            [
                [
                    'form.subclient_id' => fake()->randomNumber(),
                    'form.report_type' => fake()->word(),
                    'form.start_date' => today(),
                    'form.end_date' => fake()->word(),
                ],
                [
                    'form.subclient_id' => ['exists'],
                    'form.report_type' => ['in'],
                    'form.start_date' => ['date_format:Y-m-d'],
                    'form.end_date' => ['date'],
                ],
            ],
            [
                [
                    'form.start_date' => today()->addDay(),
                    'form.end_date' => today()->addDay(),
                ],
                [
                    'form.start_date' => ['before_or_equal:today'],
                    'form.end_date' => ['before_or_equal:today'],
                ],
            ],
            [
                [
                    'form.start_date' => today()->subYear(),
                    'form.end_date' => today(),
                ],
                [
                    'form.end_date' => ['before_or_equal:' . today()->subYear()->addDays(2)->toDateString()],
                ],
            ],
            [
                [
                    'form.start_date' => today()->subMonth(),
                    'form.end_date' => today()->subMonths(2),
                ],
                [
                    'form.end_date' => ['after_or_equal:start_date'],
                ],
            ],
        ];
    }
}
