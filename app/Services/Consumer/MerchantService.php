<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\MerchantType;
use App\Models\Consumer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MerchantService
{
    public function getMerchant(Consumer $consumer, ?MerchantType $merchantType = null): Collection
    {
        return Merchant::query()
            ->when(
                $consumer->subclient_id && $consumer->subclient?->has_merchant,
                function (Builder $query) use ($consumer): void {
                    $query->where('subclient_id', $consumer->subclient_id)
                        ->where('company_id', $consumer->company_id);
                },
                function (Builder $query) use ($consumer): void {
                    $query->whereNull('subclient_id')
                        ->where('company_id', $consumer->company_id);
                }
            )
            ->when($merchantType, function (Builder $query) use ($merchantType): void {
                $query->where('merchant_type', $merchantType);
            })
            ->get();
    }
}
