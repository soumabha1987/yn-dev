<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PersonalizedLogo;

class PersonalizedLogoService
{
    public function findBySubclient(int $companyId, ?int $subclientId): ?PersonalizedLogo
    {
        return PersonalizedLogo::query()
            ->select(['id', 'company_id', 'subclient_id', 'primary_color', 'secondary_color', 'customer_communication_link', 'size'])
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->first();
    }

    public function findByCompanyId(int $companyId): ?PersonalizedLogo
    {
        return PersonalizedLogo::query()
            ->select(['id', 'company_id', 'subclient_id', 'primary_color', 'secondary_color', 'customer_communication_link', 'size'])
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrCreate(int $companyId, ?int $subclientId, array $data)
    {
        PersonalizedLogo::query()
            ->updateOrCreate([
                'company_id' => $companyId,
                'subclient_id' => $subclientId,
            ], $data);
    }

    public function deleteByCompany(int $companyId): void
    {
        PersonalizedLogo::query()
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->delete();
    }

    public function deleteBySubclient(int $companyId, ?int $subclientId): void
    {
        PersonalizedLogo::query()
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->delete();
    }

    public function companyHasPersonalizedLogo(?int $subclientId, int $companyId): bool
    {
        return PersonalizedLogo::query()
            ->where('subclient_id', $subclientId)
            ->where('company_id', $companyId)
            ->exists();
    }
}
