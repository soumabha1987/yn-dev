<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Auth;

use App\Enums\CompanyStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Forms\Auth\RegisterForm;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.guest-layout')]
class RegisterPage extends Component
{
    public RegisterForm $form;

    public ?string $code = null;

    public bool $resetCaptcha = false;

    public function register(): void
    {
        $this->resetCaptcha = true;

        $validatedData = $this->form->validate();

        $partnerId = null;

        if ($this->code) {
            $partnerId = Partner::query()->where('registration_code', $this->code)->value('id');
        }

        $company = Company::query()->create([
            'owner_full_name' => $validatedData['name'],
            'owner_email' => $validatedData['email'],
            'status' => CompanyStatus::CREATED,
            'partner_id' => $partnerId,
        ]);

        $validatedData['company_id'] = $company->id;

        Arr::forget($validatedData, ['company_name', 'confirm_password', 'terms_and_conditions', 'recaptcha']);

        event(new Registered($user = User::query()->create($validatedData)));

        $user->assignRole(EnumRole::CREDITOR->value);

        $this->js('localStorage.clear()');

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        $this->success(__('Verification email sent! Please check your inbox and click the link to verify your email address.'));

        $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.auth.register-page')->title(__('Register'));
    }
}
