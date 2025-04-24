<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\AccountProfile;

use App\Enums\CompanyMembershipStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Livewire\Creditor\Forms\AccountProfile\BillingDetailsForm;
use App\Livewire\Creditor\Traits\Logout;
use App\Models\CompanyMembership;
use App\Models\MembershipPaymentProfile;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\MembershipTransactionService;
use App\Services\PartnerService;
use App\Services\TilledPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class BillingDetails extends Component
{
    use Logout;

    public BillingDetailsForm $form;

    public ?CompanyMembership $companyMembership;

    public bool $displaySuccessModal = false;

    public string $tilledErrorMessage = '';

    protected TilledPaymentService $tilledPaymentService;

    protected MembershipTransactionService $membershipTransactionService;

    private User $user;

    public function __construct()
    {
        $this->tilledPaymentService = app(TilledPaymentService::class);
        $this->membershipTransactionService = app(MembershipTransactionService::class);

        $this->user = Auth::user();
        $this->user->loadMissing(['company']);

        $this->companyMembership = app(CompanyMembershipService::class)->findInActiveByCompany($this->user->company_id);
    }

    public function mount(): void
    {
        $this->form->fillAddress($this->user->company);
    }

    public function storeMembershipBillingDetails(): void
    {
        $validatedData = $this->form->validate();

        $attachCustomerResponse = $this->tilledPaymentService->createOrUpdateCustomer($this->user->company, $validatedData);

        $noSuccessTransaction = $this->membershipTransactionService
            ->isSuccessDoesntExists($this->user->company_id, $this->companyMembership->membership->id);

        if ($noSuccessTransaction && $attachCustomerResponse) {
            $expMonth = Str::padLeft((string) data_get($validatedData, 'tilled_response.card.exp_month'), 2, '0');

            $membershipPaymentProfile = MembershipPaymentProfile::query()->updateOrCreate(
                ['company_id' => $this->user->company_id],
                [
                    'tilled_customer_id' => $attachCustomerResponse,
                    'tilled_payment_method_id' => data_get($validatedData, 'tilled_response.id'),
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'last_four_digit' => data_get($validatedData, 'tilled_response.card.last4'),
                    'expiry' => $expMonth . '/' . data_get($validatedData, 'tilled_response.card.exp_year'),
                    'address' => $validatedData['address'],
                    'city' => $validatedData['city'],
                    'state' => $validatedData['state'],
                    'zip' => $validatedData['zip'],
                    'response' => [],
                ]
            );

            $amount = intval(((float) $this->companyMembership->membership->price) * 100);

            $response = [];

            $currentPlanEnd = match ($this->companyMembership->membership->frequency) {
                MembershipFrequency::WEEKLY => now()->addWeek(),
                MembershipFrequency::MONTHLY => now()->addMonthNoOverflow(),
                MembershipFrequency::YEARLY => now()->addYear(),
            };

            $partnerRevenueShare = 0;

            if ($this->user->company->partner_id) {
                $partnerRevenueShare = app(PartnerService::class)
                    ->calculatePartnerRevenueShare($this->user->company->partner, $amount / 100);
            }

            if ($amount > 0) {
                $response = $this->tilledPaymentService->createPaymentIntents($amount, $validatedData['tilled_response']['id']);

                $membershipPaymentProfile->update(['response' => $response]);

                $transactionStatus = optional($response)['status'];

                if (! $transactionStatus || ! in_array($transactionStatus, ['processing', 'succeeded'])) {

                    MembershipTransaction::query()
                        ->create([
                            'company_id' => $this->user->company_id,
                            'membership_id' => $this->companyMembership->membership->id,
                            'status' => MembershipTransactionStatus::FAILED,
                            'price' => $amount / 100,
                            'response' => $response,
                            'tilled_transaction_id' => $response['id'] ?? null,
                            'plan_end_date' => $currentPlanEnd,
                            'partner_revenue_share' => $partnerRevenueShare,
                        ]);

                    $this->dispatch('close-confirm-box');

                    $this->tilledErrorMessage = data_get($response, 'last_payment_error.message', __('Payment failed check your credit card details'));

                    return;
                }
            }

            $this->companyMembership->update([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_start' => now(),
                'current_plan_end' => $currentPlanEnd,
            ]);

            MembershipTransaction::query()
                ->create([
                    'company_id' => $this->user->company_id,
                    'membership_id' => $this->companyMembership->membership->id,
                    'status' => MembershipTransactionStatus::SUCCESS,
                    'price' => $amount / 100,
                    'response' => $response,
                    'tilled_transaction_id' => $response['id'] ?? null,
                    'plan_end_date' => $currentPlanEnd,
                    'partner_revenue_share' => $partnerRevenueShare,
                ]);

            $this->user->company()->update([
                'billing_address' => $validatedData['address'],
                'billing_city' => $validatedData['city'],
                'billing_state' => $validatedData['state'],
                'billing_zip' => $validatedData['zip'],
                'approved_at' => now(),
                'approved_by' => $this->user->id,
                'current_step' => CreditorCurrentStep::COMPLETED->value,
            ]);

            $this->dispatch('close-confirm-box');

            $this->displaySuccessModal = true;

            $this->js('$confetti()');

            $this->js('localStorage.clear()');

            return;
        }

        Log::channel('daily')->error('Customer attached failed while registration', [
            'tilled_response' => $this->form->tilled_response,
            'attach_customer_response' => $attachCustomerResponse,
        ]);

        $this->dispatch('close-confirm-box');

        $this->tilledErrorMessage = __('Sorry this appears to be an invalid payment method. Please try again.');
    }

    public function render(): View
    {
        [$enableFeatures, $disableFeatures] = collect(MembershipFeatures::displayFeatures())
            ->partition(fn (string $value, string $name) => in_array($name, $this->companyMembership->membership->getAttribute('features')));

        return view('livewire.creditor.account-profile.billing-details')
            ->with([
                'enableFeatures' => $enableFeatures,
                'disableFeatures' => $disableFeatures,
            ]);
    }
}
