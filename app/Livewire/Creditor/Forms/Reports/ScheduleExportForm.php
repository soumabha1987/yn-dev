<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Reports;

use App\Enums\NewReportType;
use App\Enums\ScheduleExportDeliveryType;
use App\Enums\ScheduleExportFrequency;
use App\Models\Company;
use App\Models\CsvHeader;
use App\Models\ScheduleExport;
use App\Models\SftpConnection;
use App\Models\Subclient;
use App\Rules\MultipleEmails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ScheduleExportForm extends Form
{
    public string $report_type = '';

    public $company_id = '';

    public $subclient_id = '';

    public $sftp_connection_id = '';

    public $csv_header_id = '';

    public string $frequency = '';

    public string $delivery_type = ScheduleExportDeliveryType::EMAIL->value;

    public string $emails = '';

    public function init(ScheduleExport $scheduleExport): void
    {
        $this->fill([
            'company_id' => $scheduleExport->company_id ?? '',
            'subclient_id' => $scheduleExport->subclient_id ?? '',
            'report_type' => $scheduleExport->report_type->value,
            'sftp_connection_id' => $scheduleExport->sftp_connection_id ?? '',
            'csv_header_id' => $scheduleExport->csv_header_id ?? '',
            'frequency' => $scheduleExport->frequency->value,
            'delivery_type' => $scheduleExport->sftp_connection_id
                ? ScheduleExportDeliveryType::SFTP->value
                : ScheduleExportDeliveryType::EMAIL->value,
            'emails' => implode(', ', $scheduleExport->emails ?? []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(NewReportType::values())],
            'company_id' => ['nullable', Rule::exists(Company::class, 'id')->where('is_super_admin_company', false)],
            'subclient_id' => ['nullable', Rule::exists(Subclient::class, 'id')->where('company_id', Auth::user()->company_id)],
            'frequency' => ['required', 'string', Rule::in(ScheduleExportFrequency::values())],
            'delivery_type' => ['required', 'string', Rule::in(ScheduleExportDeliveryType::values())],
            'sftp_connection_id' => [
                Rule::requiredIf(fn () => $this->delivery_type === ScheduleExportDeliveryType::SFTP->value),
                'integer',
                Rule::exists(SftpConnection::class, 'id')
                    ->where('enabled', true)
                    ->whereNotNull('export_filepath'),
            ],
            'csv_header_id' => [
                'nullable',
                'integer',
                Rule::exists(CsvHeader::class, 'id')
                    ->where('is_mapped', true),
            ],
            'emails' => [
                Rule::requiredIf(fn () => $this->delivery_type === ScheduleExportDeliveryType::EMAIL->value),
                'string',
                new MultipleEmails,
            ],
        ];
    }
}
