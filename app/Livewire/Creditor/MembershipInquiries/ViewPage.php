<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\MembershipInquiries;

use App\Enums\MembershipInquiryStatus;
use App\Livewire\Creditor\Forms\MembershipForm;
use App\Mail\ResolveSpecialMembershipInquiryRequestMail;
use App\Models\Membership;
use App\Models\MembershipInquiry;
use App\Services\MembershipInquiryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ViewPage extends Component
{
    public MembershipForm $form;

    public MembershipInquiry $membershipInquiry;

    public bool $hasSpecialMembership = false;

    public bool $createPlan = false;

    public function mount(): void
    {
        $this->membershipInquiry->loadMissing('company.specialMembership');

        $this->hasSpecialMembership = $this->membershipInquiry->company->specialMembership !== null;

        if ($this->hasSpecialMembership) {
            $this->form->init($this->membershipInquiry->company->specialMembership);

            return;
        }

        $this->fill([
            'form.name' => $this->membershipInquiry->company->company_name,
        ]);
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        Membership::query()->updateOrCreate(
            ['company_id' => $this->membershipInquiry->company_id],
            [
                ...$validatedData,
                'status' => true,
            ]
        );

        $this->membershipInquiry->update(['status' => MembershipInquiryStatus::RESOLVED]);

        $message = $this->hasSpecialMembership
            ? __('The special membership updated successfully')
            : __('The special membership created successfully');

        if (! $this->hasSpecialMembership) {
            Mail::to($this->membershipInquiry->company->owner_email)
                ->send(new ResolveSpecialMembershipInquiryRequestMail($this->membershipInquiry));
        }

        $this->success($message);

        Cache::put(
            'new_inquires_count',
            $newInquiryCount = app(MembershipInquiryService::class)->newInquiresCount(),
            now()->addHour(),
        );

        $this->dispatch(
            'membership-inquiry-count-updated',
            $newInquiryCount,
        );

        $this->dispatch('close-dialog-box');
    }

    public function render(): View
    {
        return view('livewire.creditor.membership-inquiries.view-page');
    }
}
