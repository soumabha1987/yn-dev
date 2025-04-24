<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * @param array{
     *  company_id: int,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetch(array $data): Collection
    {
        return User::query()
            ->select('id', 'name', 'email', 'email_verified_at', 'parent_id')
            ->whereNull(['blocked_at', 'blocker_user_id'])
            ->where('company_id', $data['company_id'])
            ->when(
                $data['column'] === 'last_name',
                function (Builder $query) use ($data): void {
                    $query->orderBy(DB::raw("SUBSTRING_INDEX(name, ' ', -1)"), $data['direction'])->orderBy('id');
                },
                function (Builder $query) use ($data): void {
                    $query->orderBy($data['column'], $data['direction'])->orderBy('id');
                }
            )
            ->get();
    }

    public function fetchCount(int $companyId): int
    {
        return User::query()
            ->where('company_id', $companyId)
            ->whereNull(['blocked_at', 'blocker_user_id'])
            ->count();
    }

    /**
     * @param array{
     *  user: User,
     *  search: ?string,
     *  per_page: int,
     *  column: string,
     *  direction: string,
     * } $data
     */
    public function fetchH2H(array $data): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'email', 'phone_no'])
            ->where('company_id', $data['user']->company_id)
            ->where('is_h2h_user', true)
            ->when($data['search'], function (Builder $query) use ($data): void {
                $query->where(function (Builder $query) use ($data): void {
                    $query->search('name', $data['search'])
                        ->orSearch('email', $data['search'])
                        ->orSearch('phone_no', $data['search']);
                });
            })
            ->when($data['column'], function (Builder $query) use ($data): void {
                $query->orderBy($data['column'], $data['direction'])->orderBy('id');
            })
            ->paginate($data['per_page']);
    }

    public function deleteWithUpdateEmail(User $user): void
    {
        $user->update([
            'email' => 'deleted-' . rand(1000, 9999) . '-' . $user->email,
            'deleted_at' => now(),
        ]);
    }

    public function getAllSuperAdminEmails(): array
    {
        return User::query()
            ->whereRelation('roles', 'name', Role::SUPERADMIN)
            ->pluck('email')
            ->all();
    }

    public function getAllSuperAdminPhoneNumbers(): Collection
    {
        return User::query()
            ->whereRelation('roles', 'name', Role::SUPERADMIN)
            ->pluck('phone_no')
            ->filter(fn ($value) => filled($value))
            ->unique();
    }
}
