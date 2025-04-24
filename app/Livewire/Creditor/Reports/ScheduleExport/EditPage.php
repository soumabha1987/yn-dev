<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Reports\ScheduleExport;

use App\Enums\NewReportType;
use App\Enums\Role;
use App\Enums\ScheduleExportDeliveryType;
use App\Livewire\Creditor\Forms\Reports\ScheduleExportForm;
use App\Models\ScheduleExport;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\CsvHeaderService;
use App\Services\ScheduleExportService;
use App\Services\SftpConnectionService;
use App\Services\SubclientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Livewire\Component;

class EditPage extends Component
{
    public ScheduleExport $scheduleExport;

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
                    'schedule_export_id' => $this->scheduleExport->id,
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
        $this->reportTypes = collect(NewReportType::displaySelectionBox())
            ->when(
                $this->user->hasRole(Role::CREDITOR),
                fn (Collection $collection) => $collection->except(NewReportType::BILLING_HISTORIES->value)
            )
            ->sort()
            ->all();

        if ($this->user->hasRole(Role::CREDITOR) && $this->scheduleExport->company_id !== $this->user->company_id) {
            $this->error(__('This report URL does not match your membership credentials. Please recreate the URL link from your member account.'));

            $this->redirectRoute('creditor.schedule-export', navigate: true);

            return;
        }

        $this->form->init($this->scheduleExport);
    }

    public function update(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['emails'] = collect(explode(',', $validatedData['emails']))
            ->map(fn (string $email): string => trim($email))
            ->unique();

        $validatedData = collect($validatedData)
            ->filter(fn ($value) => filled($value))
            ->toArray();

        $validatedData['company_id'] = $this->is_super_admin
            ? $validatedData['company_id'] ?? null
            : $this->user->company_id;

        $validatedData['subclient_id'] = $validatedData['subclient_id'] ?? null;

        $validatedData['csv_header_id'] = $validatedData['csv_header_id'] ?? null;

        if ($this->form->delivery_type === ScheduleExportDeliveryType::EMAIL->value) {
            $validatedData['sftp_connection_id'] = null;
        }

        if ($this->form->delivery_type === ScheduleExportDeliveryType::SFTP->value) {
            $validatedData['emails'] = null;
        }

        Arr::forget($validatedData, ['delivery_type']);

        $this->scheduleExport->update($validatedData);

        $this->success(__('Report updated.'));

        $this->is_super_admin
            ? $this->redirectRoute('schedule-export', navigate: true)
            : $this->redirectRoute('creditor.schedule-export', navigate: true);
    }

    public function render(): View
    {
        $clients = $this->is_super_admin
            ? app(CompanyService::class)->fetchForSelectionBox()
            : app(SubclientService::class)->fetchForSelectionBox($this->user->company_id);

        return view('livewire.creditor.reports.schedule-export.edit-page')
            ->with([
                'clients' => $clients,
                'sftpConnections' => $this->is_super_admin ? [] : app(SftpConnectionService::class)->fetchExportSftpConnections($this->user->company_id),
                'mappedHeaders' => $this->is_super_admin ? [] : app(CsvHeaderService::class)->fetchOnlyMapped($this->user->company_id, $this->user->subclient_id)->pluck('name', 'id')->all(),
            ])
            ->title(__('Edit a Schedule Report'));
    }
}
