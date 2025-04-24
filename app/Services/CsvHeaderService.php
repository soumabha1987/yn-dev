<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumerFields;
use App\Models\CsvHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CsvHeaderService
{
    public function fetchByCompanyId(int $companyId, ?int $subclientId): Collection
    {
        return $this->fetch($companyId, $subclientId)
            ->mapWithKeys(fn (CsvHeader $header): array => [
                $header->id => Str::title($header->name) . ' ' . ($header->is_mapped ? __('(Completed)') : __('(Incomplete)')),
            ]);
    }

    public function fetch(int $companyId, ?int $subclientId): Collection
    {
        return CsvHeader::query()
            ->select('id', 'name', 'sftp_connection_id', 'is_mapped')
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->orderByDesc('is_mapped')
            ->get();
    }

    /**
     * @throws ModelNotFoundException<CsvHeader>
     */
    public function findById(string $id, int $companyId, ?int $subclientId): CsvHeader
    {
        return CsvHeader::query()
            ->select('id', 'is_mapped', 'headers')
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->findOrFail($id);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function create(array $data): CsvHeader
    {
        return CsvHeader::query()->create($data);
    }

    public function fetchOnlyMapped(int $companyId, ?int $subclientId): Collection
    {
        return CsvHeader::query()
            ->select('id', 'name', 'date_format', 'headers')
            ->where('is_mapped', true)
            ->where('company_id', $companyId)
            ->when($subclientId, function (Builder $query) use ($subclientId): void {
                $query->where('subclient_id', $subclientId);
            })
            ->get()
            ->whenNotEmpty(function (Collection $headers): Collection {
                return $headers->map(function (CsvHeader $csvHeader): array {
                    return [
                        'id' => $csvHeader->id,
                        'name' => $csvHeader->name,
                        'date_format' => $csvHeader->date_format,
                        'headers' => collect($csvHeader->getAttribute('mapped_headers'))
                            ->sortBy(fn (string $header, string $key): int => array_search($key, ConsumerFields::values()))
                            ->mapWithKeys(fn ($header, $key) => [ConsumerFields::tryFromValue($key)->displayName() => $header])
                            ->toArray(),
                    ];
                })->values();
            });
    }

    public function isExists(?int $subclientId, int $companyId): bool
    {
        return CsvHeader::query()
            ->where('company_id', $companyId)
            ->when($subclientId, fn ($query) => $query->where('subclient_id', $subclientId))
            ->where('is_mapped', true)
            ->exists();
    }
}
