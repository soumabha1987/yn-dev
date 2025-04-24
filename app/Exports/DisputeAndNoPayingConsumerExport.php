<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DisputeAndNoPayingConsumerExport implements FromCollection, WithHeadingRow, WithHeadings
{
    public function __construct(
        private Collection $consumers,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(fn (Consumer $consumer): array => [
            'disputed_at' => $consumer->disputed_at->formatWithTimezone(format: 'M d, Y h:i A'),
            'account_balance' => Number::currency((float) $consumer->current_balance),
            'consumer_name' => $consumer->first_name . ' ' . $consumer->last_name,
            'master_account_number' => $consumer->member_account_number,
            'original_account_name' => $consumer->original_account_name,
            'sub_account_name' => $consumer->subclient_name,
            'placement_date' => $consumer->placement_date,
        ]);
    }

    public function headings(): array
    {
        return [
            __('Date/Time'),
            __('Account Balance'),
            __('Consumer name'),
            __('Account #'),
            __('Account Name'),
            __('Sub Account Name'),
            __('Placement Date'),
        ];
    }
}
