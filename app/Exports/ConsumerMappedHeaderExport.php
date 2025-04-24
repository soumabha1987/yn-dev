<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Consumer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsumerMappedHeaderExport implements FromCollection, WithHeadings
{
    public function __construct(
        private array $mappedHeaders,
        private Collection $consumers,
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->consumers->map(function (Consumer $consumer): array {
            $data = [];

            foreach (array_keys($this->mappedHeaders) as $column) {
                $data[$column] = $consumer->{$column};
            }

            return $data;
        });
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_values($this->mappedHeaders);
    }
}
