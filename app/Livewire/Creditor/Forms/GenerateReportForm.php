<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Enums\ReportType;
use App\Enums\Role;
use App\Livewire\Creditor\Reports\GenerateReport\IndexPage;
use App\Models\Company;
use App\Models\Subclient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class GenerateReportForm extends Form
{
    public $start_date;

    public $end_date;

    public $report_type;

    public $subclient_id;

    public $company_id;

    public function rules(): array
    {
        /** @var IndexPage $component */
        $component = $this->component;

        $user = Auth::user();

        $subclientRule = ['subclient_id' => 'nullable'];

        if ($user->hasRole(Role::CREDITOR)) {
            $subclientRule = [
                'subclient_id' => ['required',
                    Rule::when(
                        $this->subclient_id !== 'master',
                        [
                            Rule::exists(Subclient::class, 'id')
                                ->where('company_id', $user->company_id),
                        ],
                    ),
                ],
            ];
        }

        return [
            ...$subclientRule,
            'report_type' => ['required', Rule::in(array_keys($component->reportTypes))],
            'company_id' => [
                'nullable',
                Rule::exists(Company::class, 'id')
                    ->where('is_super_admin_company', false),
            ],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                Rule::when($this->report_type !== ReportType::SCHEDULED_TRANSACTIONS->value, ['before_or_equal:today']),
                Rule::when($this->report_type === ReportType::SCHEDULED_TRANSACTIONS->value, ['after_or_equal:today']),
            ],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                Rule::when(
                    $this->report_type === ReportType::SCHEDULED_TRANSACTIONS->value,
                    rules: ['before_or_equal:' . Carbon::parse($this->start_date)->addMonths(2)->toDateString()],
                    defaultRules: [
                        'before_or_equal:today',
                        'before_or_equal:' . Carbon::parse($this->start_date)->addMonths(2)->toDateString(),
                    ]
                ),
            ],
        ];
    }
}
