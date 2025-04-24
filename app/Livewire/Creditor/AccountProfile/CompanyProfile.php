<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AccountProfile;

use App\Livewire\Creditor\Forms\AccountProfile\CompanyProfileForm;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class CompanyProfile extends Component
{
    public CompanyProfileForm $form;

    private Company $company;

    public function __construct()
    {
        $user = Auth::user();

        $user->loadMissing('company');

        $this->company = $user->company;
    }

    public function mount(): void
    {
        $this->form->init($this->company);
    }

    public function store(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['from_time'] = Carbon::parse($validatedData['from_time'], $validatedData['timezone'])->utc();
        $validatedData['to_time'] = Carbon::parse($validatedData['to_time'], $validatedData['timezone'])->utc();

        $validatedData['billing_email'] = $validatedData['owner_email'];
        $validatedData['billing_phone'] = $validatedData['owner_phone'];

        if (! Str::startsWith($validatedData['url'], ['http://', 'https://'])) {
            $validatedData['url'] = 'http://' . ltrim($validatedData['url'], '/');
        }

        $this->company->update($validatedData);

        $this->js('localStorage.clear()');

        $this->dispatch('next')->to(IndexPage::class);
    }

    public function render(): View
    {
        return view('livewire.creditor.account-profile.company-profile');
    }
}
