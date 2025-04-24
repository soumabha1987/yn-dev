<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageCreditors;

use App\Enums\ConsumerStatus;
use App\Enums\Role;
use App\Exports\ConsumersExport;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Models\Company;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\CompanyService;
use App\Services\ConsumerService;
use App\Services\MembershipInquiryService;
use App\Services\SetupWizardService;
use App\Services\UserService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $onlyTrashed = false;

    private User $user;

    protected UserService $userService;

    protected SetupWizardService $setupWizardService;

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'created_on';
        $this->sortAsc = false;
        $this->user = Auth::user();
        $this->userService = app(UserService::class);
        $this->setupWizardService = app(SetupWizardService::class);
    }

    public function updatedOnlyTrashed(): void
    {
        $this->resetPage();
    }

    public function switchBlockStatus(Company $company): void
    {
        $company->update(['is_deactivate' => ! $company->is_deactivate]);

        $this->dispatch('close-menu-item');

        $this->success(__(':member_name :value. Users :action log in.', [
            'member_name' => $company->company_name,
            'value' => $company->is_deactivate ? __('blocked') : __('unblocked'),
            'action' => $company->is_deactivate ? 'cannot' : 'can',
        ]));
    }

    public function login(Company $company): void
    {
        if ($company->is_deactivate || blank($company->creditorUser)) {
            $this->error(__('This email has been blocked by YN Admin - . Please email help@younegotiate.com for help or questions.'));

            $this->dispatch('close-menu-item');

            return;
        }

        /** @var User $user */
        $user = $company->creditorUser;

        Auth::login($user);

        $this->dispatch('close-menu-item');

        $this->redirectIntended(RouteServiceProvider::HOME, navigate: true);
    }

    public function delete(Company $company): void
    {
        if (app(ConsumerService::class)->isPaymentAcceptedForCompany($company->id)) {
            $this->error(__('Active Consumer Payment Plans. This member account cannot be deleted.'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $company->loadMissing('users');

        /** @var User $user */
        $user = $company->creditorUser;

        if ($user->hasRole(Role::SUPERADMIN)) {
            $this->error(__('Apologies, this company cannot be deleted as it has a super admin'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        DB::beginTransaction();

        try {
            $company->scheduleExports()->delete();
            $company->merchants()->delete();
            $company->users()->cursor()->each(function (User $user): void {
                $this->userService->deleteWithUpdateEmail($user);
            });
            $company->consumers()->update([
                'status' => ConsumerStatus::DEACTIVATED,
                'disputed_at' => now(),
            ]);

            $company->delete();

            Cache::put(
                'new_inquires_count',
                $newInquiryCount = app(MembershipInquiryService::class)->newInquiresCount(),
                now()->addHour(),
            );

            $this->dispatch(
                'membership-inquiry-count-updated',
                $newInquiryCount,
            );

            DB::commit();

            $this->success(__('Member account has been deleted.'));
        } catch (Exception) {
            DB::rollBack();

            $this->error(__('No active payment plans. Problem deleting one of the following: scheduled exports/sub accounts and/or users. Contact tech support.'));
        }

        $this->dispatch('close-confirmation-box');
    }

    public function exportConsumers(Company $company): ?BinaryFileResponse
    {
        $consumers = app(ConsumerService::class)->exportConsumers($company);

        if (blank($consumers)) {
            $this->error(__('No consumer accounts available.'));

            return null;
        }

        $fileName = Str::of($company->company_name)->slug('_')
            ->append('_', now()->format('Y_m_d_H_i_s'), '.csv')
            ->toString();

        $this->success(__('Member consumers list exported.'));

        $this->dispatch('close-notification-' . $company->id);

        return Excel::download(new ConsumersExport($consumers, $this->user), $fileName);
    }

    private function isSetupWizardCompleted(LengthAwarePaginator $companies): LengthAwarePaginator
    {
        $companies->each(function (Company $company): void {
            if (! $company->trashed()) {
                $company->setAttribute('isSetupWizardCompleted', $this->setupWizardService->isRequiredStepsCompleted($company->creditorUser, $company));
            }
        });

        return $companies;
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'name' => 'company_name',
            'status' => 'status',
            'merchant_status' => 'status',
            'category' => 'business_category',
            'created_on' => 'created_at',
            'owner_full_name' => 'owner_full_name',
            'consumers_count' => 'consumers_count',
            'total_balance' => 'consumers_sum_current_balance',
            default => 'created_at',
        };

        $data = [
            'per_page' => $this->perPage,
            'search' => $this->search,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'only_trashed' => $this->onlyTrashed,
        ];

        return view('livewire.creditor.manage-creditors.list-page')
            ->with('companies', $this->isSetupWizardCompleted(app(CompanyService::class)->fetchWithTrashed($data)))
            ->title(__('Manage Creditors'));
    }
}
