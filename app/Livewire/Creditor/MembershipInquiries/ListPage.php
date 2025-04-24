<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\MembershipInquiries;

use App\Enums\MembershipInquiryStatus;
use App\Livewire\Creditor\Traits\Sortable;
use App\Livewire\Traits\WithPagination;
use App\Mail\CloseSpecialMembershipInquiryMail;
use App\Models\MembershipInquiry;
use App\Services\MembershipInquiryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Url;
use Livewire\Component;

class ListPage extends Component
{
    use Sortable;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function __construct()
    {
        $this->withUrl = true;
        $this->sortCol = 'created_on';
        $this->sortAsc = false;
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function closeInquiry(MembershipInquiry $membershipInquiry): void
    {
        $membershipInquiry->update(['status' => MembershipInquiryStatus::CLOSE]);

        $this->success(__('Membership inquiry has been successfully closed.'));

        Cache::put(
            'new_inquires_count',
            $newMembershipInquiryCount = app(MembershipInquiryService::class)->newInquiresCount(),
            now()->addHour(),
        );

        $this->dispatch('membership-inquiry-count-updated', $newMembershipInquiryCount);

        Mail::to($membershipInquiry->company->owner_email)
            ->send(new CloseSpecialMembershipInquiryMail($membershipInquiry->company));
    }

    public function render(): View
    {
        $column = match ($this->sortCol) {
            'created_on' => 'created_at',
            'company_name' => 'company_name',
            'email' => 'owner_email',
            'phone' => 'owner_phone',
            'account-in-scope' => 'accounts_in_scope',
            'status' => 'status',
            default => 'created_at'
        };

        $data = [
            'per_page' => $this->perPage,
            'column' => $column,
            'direction' => $this->sortAsc ? 'ASC' : 'DESC',
            'search' => $this->search,
        ];

        return view('livewire.creditor.membership-inquiries.list-page')
            ->with('membershipInquiries', app(MembershipInquiryService::class)->fetchWithCompany($data))
            ->title(__('Membership Inquiries'));
    }
}
