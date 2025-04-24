<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CustomContentType;
use App\Models\CustomContent;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomContentService
{
    /**
     * @param array{
     *     per_page: int,
     *     company_id: int,
     *     column: string,
     *     direction: string,
     * } $data
     */
    public function fetchTermsAndConditions(array $data): LengthAwarePaginator
    {
        return CustomContent::query()
            ->select('id', 'company_id', 'subclient_id', 'type', 'content', 'created_at')
            ->with([
                'subclient' => fn (BelongsTo $belongsTo) => $belongsTo->select('id', 'subclient_name'),
            ])
            ->where(function (Builder $query): void {
                $query->whereNull('subclient_id')
                    ->orWhereHas('subclient');
            })
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS)
            ->where('company_id', $data['company_id'])
            ->when(
                $data['column'] === 'subclient_name',
                function (Builder $query) use ($data): void {
                    $query->orderBy(
                        Subclient::query()
                            ->select('subclient_name')
                            ->whereColumn('custom_contents.subclient_id', 'subclients.id'),
                        $data['direction']
                    );
                },
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction']);
                }
            )
            ->orderBy('id')
            ->paginate($data['per_page']);
    }

    public function fetchAboutUs(int $companyId): ?CustomContent
    {
        return CustomContent::query()
            ->select('id', 'company_id', 'subclient_id', 'type', 'content')
            ->with('company:id,company_name')
            ->where('type', CustomContentType::ABOUT_US)
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->first();
    }

    public function defaultTermsAndConditionDoesntExists(User $user): bool
    {
        return CustomContent::query()
            ->where('company_id', $user->company_id)
            ->where('subclient_id', $user->subclient_id)
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS)
            ->doesntExist();
    }

    public function defaultAboutUsDoesntExists(User $user): bool
    {
        return CustomContent::query()
            ->where('company_id', $user->company_id)
            ->where('subclient_id', $user->subclient_id)
            ->where('type', CustomContentType::ABOUT_US)
            ->doesntExist();
    }

    public function findByCompany(int $companyId): ?CustomContent
    {
        return CustomContent::query()
            ->select('content')
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS)
            ->first();
    }

    public function findBySubclient(int $companyId, int $subclientId): ?CustomContent
    {
        return CustomContent::query()
            ->select('content')
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS)
            ->first();
    }

    public function findByCompanyOrSubclient(int $companyId, ?int $subclientId): ?CustomContent
    {
        return CustomContent::query()
            ->select('content')
            ->where('company_id', $companyId)
            ->where('type', CustomContentType::TERMS_AND_CONDITIONS)
            ->where(function ($query) use ($subclientId) {
                $query->where('subclient_id', $subclientId)
                    ->orWhereNull('subclient_id');
            })
            ->first();
    }

    public function findAboutUsBySubclient(int $companyId, int $subclientId): ?CustomContent
    {
        return CustomContent::query()
            ->select('content')
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->where('type', CustomContentType::ABOUT_US)
            ->first();
    }
}
