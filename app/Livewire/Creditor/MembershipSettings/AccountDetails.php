<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\MembershipSettings;

use App\Models\MembershipPaymentProfile;
use App\Models\User;
use App\Services\MembershipPaymentProfileService;
use App\Services\TilledPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class AccountDetails extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $last_four_digit_of_card_number = '';

    public string $expiry = '';

    public string $zip = '';

    public ?MembershipPaymentProfile $membershipPaymentProfile;

    /**
     * @return array<string, mixed>
     */
    public array $tilled_response = [];

    #[Url]
    public bool $accountDetailsDialogOpen = false;

    protected MembershipPaymentProfileService $membershipPaymentProfileService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->membershipPaymentProfileService = app(MembershipPaymentProfileService::class);
    }

    public function mount(): void
    {
        $this->membershipPaymentProfile = $this->membershipPaymentProfileService->fetchByCompany($this->user->company_id);

        $this->fill([
            'first_name' => $this->membershipPaymentProfile->first_name ?? '',
            'last_name' => $this->membershipPaymentProfile->last_name ?? '',
            'last_four_digit_of_card_number' => $this->membershipPaymentProfile->last_four_digit ?? '',
            'expiry' => $this->membershipPaymentProfile->expiry ?? '',
            'zip' => $this->membershipPaymentProfile->zip ?? $this->user->company->zip ?? $this->user->company->owner_zip ?? '',
        ]);
    }

    public function updateDetails(): void
    {
        $validatedData = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'tilled_response' => ['required', 'array'],
            'tilled_response.id' => ['required', 'string'],
            'tilled_response.card.last4' => ['required', 'numeric', 'digits:4'],
            'tilled_response.card.exp_month' => ['required', 'integer', 'min:1', 'max:12', 'max_digits:2'],
            'tilled_response.card.exp_year' => ['required', 'integer', 'digits:4'],
        ]);

        $customerId = app(TilledPaymentService::class)->createOrUpdateCustomer($this->user->company, $validatedData);

        if ($customerId) {
            $expMonth = Str::padLeft((string) data_get($validatedData, 'tilled_response.card.exp_month'), 2, '0');

            $this->membershipPaymentProfile->update([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'last_four_digit' => data_get($validatedData, 'tilled_response.card.last4'),
                'expiry' => $expMonth . '/' . data_get($validatedData, 'tilled_response.card.exp_year'),
                'tilled_payment_method_id' => data_get($validatedData, 'tilled_response.id'),
                'tilled_customer_id' => $customerId,
                'zip' => $this->zip,
                'response' => $validatedData['tilled_response'],
            ]);

            $this->fill([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'last_four_digit_of_card_number' => data_get($validatedData, 'tilled_response.card.last4'),
                'expiry' => $expMonth . '/' . data_get($validatedData, 'tilled_response.card.exp_year'),
            ]);

            $this->success(__('Your payment method has been updated!'));

            return;
        }

        $this->error(__('Sorry this appears to be an invalid payment method. Please try again.'));
    }

    public function render(): View
    {
        return view('livewire.creditor.membership-settings.account-details');
    }
}
