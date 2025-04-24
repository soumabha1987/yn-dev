<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports\ScheduleExport;

use App\Enums\FeatureName;
use App\Enums\NewReportType;
use App\Enums\Role;
use App\Livewire\Creditor\Forms\Reports\ScheduleExportForm;
use App\Models\ScheduleExport;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\CsvHeaderService;
use App\Services\FeatureFlagService;
use App\Services\ScheduleExportService;
use App\Services\SftpConnectionService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Livewire\Component;

class CreatePage extends Component
{
    public ScheduleExportForm $form;

    public array $reportTypes = [];

    private User $user;

    private bool $is_super_admin;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->is_super_admin = $this->user->hasRole(Role::SUPERADMIN);
    }

    public function boot(): void
    {
        $this->form->withValidator(function ($validator) {
            $validator->after(function (Validator $validator) {
                $data = $validator->getData();

                $data = [
                    'is_super_admin' => $this->is_super_admin,
                    'report_type' => $data['report_type'],
                    'frequency' => $data['frequency'],
                    'user_id' => $this->user->id,
                    'company_id' => $this->is_super_admin
                        ? (filled($data['company_id']) ? $data['company_id'] : null)
                        : $this->user->company_id,
                    'subclient_id' => filled($data['subclient_id']) ? $data['subclient_id'] : null,
                    'csv_header_id' => filled($data['csv_header_id']) ? $data['csv_header_id'] : null,
                    'sftp_connection_id' => filled($data['sftp_connection_id']) ? $data['sftp_connection_id'] : null,
                    'schedule_export_id' => null,
                    'delivery_type' => $data['delivery_type'],
                ];

                $scheduleExport = app(ScheduleExportService::class)->sameReportExists($data);

                if (filled($scheduleExport)) {
                    $validator->errors()->add(
                        'email',
                        __(
                            'Sorry this schedule report already exists :url',
                            ['url' => "<a href='" .
                                (
                                    $this->is_super_admin
                                    ? route('schedule-export.edit', $scheduleExport->id)
                                    : route('creditor.schedule-export.edit', $scheduleExport->id)
                                ) . "' class='font-bold'>click here to edit</a>",
                            ]
                        )
                    );
                }
            });
        });
    }

    public function mount(): void
    {
        if (app(FeatureFlagService::class)->disabled(FeatureName::SCHEDULE_EXPORT)) {
            $this->info(__('Unable to create a scheduled export: The scheduled export functionality is currently disabled.'));

            $this->is_super_admin
                ? $this->redirectRoute('schedule-export', navigate: true)
                : $this->redirectRoute('creditor.schedule-export', navigate: true);

            return;
        }

        $this->reportTypes = collect(NewReportType::displaySelectionBox())
            ->when(
                $this->user->hasRole(Role::CREDITOR),
                fn (Collection $collection) => $collection->except(NewReportType::BILLING_HISTORIES->value)
            )
            ->sort()
            ->all();
    }

    public function create(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['emails'] = collect(explode(',', $validatedData['emails']))
            ->map(fn (string $email): string => trim($email))
            ->unique();

        $validatedData['user_id'] = $this->user->id;

        $validatedData = collect($validatedData)
            ->filter(fn ($value) => filled($value))
            ->toArray();

        $validatedData['company_id'] = $this->is_super_admin
            ? $validatedData['company_id'] ?? null
            : $this->user->company_id;

        Arr::forget($validatedData, ['delivery_type']);

        ScheduleExport::query()->create($validatedData);

        $this->success(__('Report Scheduled.'));

        $this->is_super_admin
            ? $this->redirectRoute('schedule-export', navigate: true)
            : $this->redirectRoute('creditor.schedule-export', navigate: true);
    }

    public function render(): View
    {
        $clients = $this->is_super_admin
            ? app(CompanyService::class)->fetchForSelectionBox()
            : app(SubclientService::class)->fetchForSelectionBox($this->user->company_id);

        return view('livewire.creditor.reports.schedule-export.create-page')
            ->with([
                'clients' => $clients,
                'sftpConnections' => $this->is_super_admin ? [] : app(SftpConnectionService::class)->fetchExportSftpConnections($this->user->company_id),
                'mappedHeaders' => $this->is_super_admin ? [] : app(CsvHeaderService::class)->fetchOnlyMapped($this->user->company_id, $this->user->subclient_id)->pluck('name', 'id')->all(),
            ])
            ->title(__('Create a Schedule Report'));
    }
}
