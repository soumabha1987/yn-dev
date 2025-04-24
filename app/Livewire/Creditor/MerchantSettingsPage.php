<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Enums\CompanyBusinessCategory;
use App\Enums\CompanyStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Livewire\Creditor\Forms\MerchantSettings\AuthorizeForm;
use App\Livewire\Creditor\Forms\MerchantSettings\StripeForm;
use App\Livewire\Creditor\Forms\MerchantSettings\TilledForm;
use App\Livewire\Creditor\Forms\MerchantSettings\USAEpayForm;
use App\Models\Company;
use App\Models\Merchant;
use App\Models\User;
use App\Services\ConsumerService;
use App\Services\MerchantService;
use App\Services\SetupWizardService;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MerchantSettingsPage extends Component
{
    public bool $isNotEditable = true;

    public string $merchant_name = '';

    public Company $company;

    public TilledForm $tilledForm;

    public StripeForm $stripeForm;

    public AuthorizeForm $authorizeForm;

    public USAEpayForm $usaEpayForm;

    protected MerchantService $merchantService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->merchantService = app(MerchantService::class);
    }

    public function mount(): void
    {
        $this->company = $this->user->company;
        $this->merchant_name = MerchantName::AUTHORIZE->value;
        $this->isNotEditable = false;

        $merchant = $this->merchantService->getByCompany($this->user->company_id);

        $this->tilledForm->setData($this->company);

        if ($merchant) {
            $this->isNotEditable = app(ConsumerService::class)->isPaymentAcceptedAndPaymentSetupForCompany($this->company->id);
            $this->merchant_name = $merchant->merchant_name->value;

            $merchants = collect();

            if (in_array($this->merchant_name, MerchantName::filterACHAndCCMerchants())) {
                $merchants = $this->merchantService->fetchByNameAndCompany($this->merchant_name, $this->company->id);
            }

            match ($this->merchant_name) {
                MerchantName::STRIPE->value => $this->stripeForm->setData($merchant),
                MerchantName::AUTHORIZE->value => $this->authorizeForm->setData($merchants),
                MerchantName::USA_EPAY->value => $this->usaEpayForm->setData($merchants),
                default => null,
            };
        }
    }

    public function updateMerchantSettings(): void
    {
        if ($this->isNotEditable) {
            $this->error(__('Updating merchant settings is restricted because consumers already have active plans.'));

            return;
        }

        if (
            $this->merchant_name === MerchantName::YOU_NEGOTIATE->value
            && in_array($this->user->company->business_category, CompanyBusinessCategory::notAllowedYouNegotiateMerchant())
        ) {
            $this->error(__('Your company\'s business category is not allowed to use the YouNegotiate merchant.'));

            return;
        }

        $validatedData = $this->validate([
            'merchant_name' => ['required', 'string', Rule::in(MerchantName::values())],
        ]);

        $validatedData += match ($validatedData['merchant_name']) {
            MerchantName::YOU_NEGOTIATE->value => $this->tilledForm->validate(),
            MerchantName::AUTHORIZE->value => $this->authorizeForm->validate(),
            MerchantName::USA_EPAY->value => $this->usaEpayForm->validate(),
            MerchantName::STRIPE->value => $this->stripeForm->validate(),
            default => [],
        };

        $isVerified = $this->merchantService->verify($this->company, $validatedData);

        if (! $isVerified) {
            return;
        }

        $this->merchantService->deleteByCompany($this->company->id);

        if ($validatedData['merchant_name'] === MerchantName::YOU_NEGOTIATE->value) {
            collect(MerchantType::cases())->each(function (BackedEnum $merchantType) use ($validatedData) {
                Merchant::query()->create([
                    'company_id' => $this->company->id,
                    'merchant_name' => $validatedData['merchant_name'],
                    'merchant_type' => $merchantType->value,
                    'verified_at' => now()->toDateTimeString(),
                ]);
            });

            $validatedData['bank_account_number'] = Str::substr($validatedData['bank_account_number'], -2);

            if ($validatedData['contact_address'] ?? false) {
                $validatedData['owner_address'] = $validatedData['contact_address'];
                $validatedData['owner_city'] = $validatedData['contact_city'];
                $validatedData['owner_state'] = $validatedData['contact_state'];
                $validatedData['owner_zip'] = $validatedData['contact_zip'];

                Arr::forget($validatedData, ['contact_address', 'contact_city', 'contact_state', 'contact_zip']);
            }

            Arr::forget($validatedData, ['merchant_name', 'ssn']);

            $this->company->update($validatedData);

            $this->success(__('Updates successfully submitted to merchant processing team.'));

            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);

            $this->redirectRoute('home', navigate: true);

            return;
        }

        if (app(SetupWizardService::class)->isLastRequiredStepRemaining($this->user)) {
            Session::put('show-wizard-completed-modal', true);

            Cache::forget('remaining-wizard-required-steps-' . $this->user->id);
        }

        // TODO: We need to delete the merchant in tilled js.
        // Ref: https://docs.tilled.com/api/#tag/Accounts/operation/DeleteConnectedAccount
        $this->company->update([
            'tilled_profile_completed_at' => null,
            'tilled_merchant_account_id' => null,
            'status' => CompanyStatus::ACTIVE,
        ]);

        $merchantType = $validatedData['merchant_type'] ?? [MerchantType::CC->value];

        Arr::forget($validatedData, 'merchant_type');

        collect($merchantType)
            ->each(
                function (string $merchantType) use ($validatedData): void {
                    Merchant::query()->create([
                        'company_id' => $this->company->id,
                        'merchant_type' => $merchantType,
                        'verified_at' => now()->toDateTimeString(),
                        ...$validatedData,
                    ]);
                }
            );

        $this->success(__('Merchant credentials accepted.'));

        $resetData = match ($validatedData['merchant_name']) {
            MerchantName::AUTHORIZE->value => function (): void {
                $this->stripeForm->reset();
                $this->usaEpayForm->reset();
            },
            MerchantName::STRIPE->value => function (): void {
                $this->authorizeForm->reset();
                $this->usaEpayForm->reset();
            },
            MerchantName::USA_EPAY->value => function (): void {
                $this->authorizeForm->reset();
                $this->stripeForm->reset();
            },
            default => fn () => null,
        };

        $resetData();

        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.creditor.merchant-settings-page')
            ->title(__('Merchant Settings'));
    }
}
