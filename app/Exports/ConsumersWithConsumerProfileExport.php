<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\State;
use App\Models\Consumer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumersWithConsumerProfileExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            /** @var ?State $state */
            $state = $consumer->consumerProfile->state ?? $consumer->state;

            /** @var Carbon $dob */
            $dob = $consumer->dob;

            return [
                'first_name' => $consumer->consumerProfile->first_name ?? $consumer->first_name ?? '',
                'last_name' => $consumer->last_name,
                'date_of_birth' => $dob->format('M d, Y'),
                'last_four_ssn' => $consumer->last4ssn,
                'email_permission' => $consumer->consumerProfile->email_permission ? __('Yes') : __('No'),
                'email' => $consumer->consumerProfile->email ?? $consumer->email1,
                'text_permission' => $consumer->consumerProfile->text_permission ? __('Yes') : __('No'),
                'mobile' => $consumer->consumerProfile->mobile ?? $consumer->mobile1,
                'state' => $state->displayName(),
                'city' => $consumer->consumerProfile->city ?? $consumer->city ?? '',
                'zip' => $consumer->consumerProfile->zip ?? $consumer->zip ?? '',
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('First Name'),
            __('Last Name'),
            __('Date of Birth'),
            __('Social Security'),
            __('Email permission'),
            __('Email'),
            __('Text Permission'),
            __('Mobile Number'),
            __('State'),
            __('City'),
            __('Zip'),
        ];
    }
}
