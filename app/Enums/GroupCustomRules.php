<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\SelectionBox;
use App\Enums\Traits\Values;
use App\Models\Consumer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

enum GroupCustomRules: string
{
    use SelectionBox;
    use Values;

    case BALANCE_RANGE = 'current_balance';
    case CITY = 'city';
    case DATE_OF_BIRTH = 'dob';
    case EXPIRATION_DATE = 'expiry_date';
    case PLACEMENT_DATE = 'placement_date';
    case STATE = 'state';
    case UPLOAD_DATE = 'created_at';
    case ZIP = 'zip';

    public function getBuilder(Builder $query, string|array $data): void
    {
        $query->when(
            is_array($data),
            function (Builder $query) use ($data): void {
                $query->whereBetween($this->value, [head($data), last($data)]);
            },
            function (Builder $query) use ($data): void {
                $query->where($this->value, $data);
            }
        );
    }

    public function isValidConsumer(Consumer $consumer, array|string $data): bool
    {
        return match ($this) {
            self::UPLOAD_DATE => $consumer->created_at->between(head($data), last($data)),
            self::DATE_OF_BIRTH => in_array($consumer->dob->year, range(head($data), last($data))),
            self::EXPIRATION_DATE => in_array($consumer->expiry_date?->year, range(head($data), last($data))),
            self::PLACEMENT_DATE => $consumer->placement_date->between(head($data), last($data)),
            self::BALANCE_RANGE => $consumer->current_balance >= head($data) && $consumer->current_balance <= last($data),
            self::STATE => $consumer->state === $data,
            self::CITY => $consumer->city === $data,
            self::ZIP => $consumer->zip === $data,
        };
    }

    public function displayValuesString(array|string $data): string
    {
        return match ($this) {
            self::UPLOAD_DATE, self::PLACEMENT_DATE => 'Start Date is ' . Carbon::parse($data['start_date'])->format('M d, Y') . ' and End Date: ' . Carbon::parse($data['end_date'])->format('M d, Y'),
            self::BALANCE_RANGE => 'Minimum Balance is ' . Number::currency((float) $data['minimum_balance']) . ' and Maximum Balance: ' . Number::currency((float) $data['maximum_balance']),
            self::DATE_OF_BIRTH, self::EXPIRATION_DATE => 'From Year ' . $data['from_year'] . ' To Year ' . $data['to_year'],
            self::STATE => State::tryFrom($data)->displayName(),
            self::CITY, self::ZIP => $data,
        };
    }
}
