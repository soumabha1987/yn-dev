<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\Forms\PaymentForm;
use App\Livewire\Consumer\Traits\Payments\InstallmentDetails;
use App\Livewire\Consumer\Traits\Payments\PaymentProfiles;
use App\Livewire\Consumer\Traits\CreditorDetails;
use App\Livewire\Consumer\Traits\Agreement;
use App\Models\Consumer;
use App\Services\CampaignTrackerService;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\MerchantService;
use App\Services\CustomContentService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class Payment extends Component
{
    use InstallmentDetails;
    use PaymentProfiles;
    use CreditorDetails;
    use Agreement;

    public Consumer $consumer;

    public Collection $merchants;

    public PaymentForm $form;

    public bool $isDisplayName = false;

    protected DiscountService $discountService;


    public function __construct()
    {
        $this->discountService = app(DiscountService::class);
    }

    public function boot(): void
    {
        if ($this->consumer->status === ConsumerStatus::SETTLED) {
            $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer], navigate: true);
        }

        $this->form->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($this->merchants->contains('merchant_name', MerchantName::YOU_NEGOTIATE)) {
                    if (blank($this->form->tilled_response)) {
                        $validator->errors()->addIf(
                            $this->form->method === MerchantType::CC->value,
                            'card_number',
                            __('Invalid payment details, please try again.')
                        );

                        $validator->errors()->addIf(
                            $this->form->method === MerchantType::ACH->value,
                            'account_number',
                            __('Invalid payment details, please try again.')
                        );
                    }

                    if (blank($this->form->payment_method_id)) {
                        $validator->errors()->addIf(
                            $this->form->method === MerchantType::CC->value,
                            'card_number',
                            __('Invalid payment details, please try again.')
                        );

                        $validator->errors()->addIf(
                            $this->form->method === MerchantType::ACH->value,
                            'account_number',
                            __('Invalid payment details, please try again.')
                        );
                    }
                }
            });
        });
    }

    public function mount(): void
    {
        $this->consumer->loadMissing(['consumerNegotiation', 'paymentProfile', 'subclient']);

        if (blank($this->consumer->consumerNegotiation)) {
            $this->redirectRoute('consumer.negotiate', ['consumer' => $this->consumer->id], navigate: true);

            return;
        }

        if (
            // Here we can not use `===` operator because we dont use strict type checking here!
            $this->consumer->consumerNegotiation->payment_plan_current_balance == 0
            && ($this->consumer->status === ConsumerStatus::SETTLED || $this->consumer->current_balance == 0)
        ) {
            $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer->id], navigate: true);

            return;
        }

        $this->merchants = app(MerchantService::class)->getMerchant($this->consumer);

        $this->form->init($this->consumer, $this->merchants);
    }

    public function makePayment(): void
    {
        $validatedData = $this->form->validate();
        if ($validatedData['save_card']) {
            $this->saveCard($validatedData);
        }
        dd('HI');
        DB::beginTransaction();

        try {
            $this->consumer->paymentProfiles()->delete();

            $validatedData['first_name'] = $this->consumer->first_name;
            $validatedData['last_name'] = $this->consumer->last_name;

            match ($this->merchants->first()->merchant_name) {
                MerchantName::AUTHORIZE => $this->createOrUpdateAuthorizeCustomerProfile(array_filter($validatedData)),
                MerchantName::STRIPE => $this->createOrUpdateStripeCustomerProfile(array_filter($validatedData)),
                MerchantName::USA_EPAY => $this->createOrUpdateUsaEpayCustomerProfile(array_filter($validatedData)),
                MerchantName::YOU_NEGOTIATE => $this->createOrUpdateTilledCustomerProfile(array_filter($validatedData)),
                default => throw new Exception('Something went wrong..')
            };

            $data['payment_setup'] = true;

            if ($this->consumer->status === ConsumerStatus::JOINED && $this->consumer->custom_offer === false) {
                $data['status'] = ConsumerStatus::PAYMENT_ACCEPTED;
                $data['offer_accepted'] = true;

                $this->consumer->consumerNegotiation()->update([
                    'active_negotiation' => true,
                    'offer_accepted' => true,
                ]);
            }

            $this->consumer->update($data);
            if ($validatedData['save_card']) {
                $this->saveCard($validatedData);
            }
            $column = null;
            if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
                $column = 'ppl_completed_count';
            }

            if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF) {
                $column = 'pif_completed_count';
            }

            app(CampaignTrackerService::class)->updateTrackerCount($this->consumer, $column);

            Session::put(
                $this->userIsComeForPifPayment()['user_is_come_for_pif_payment'] && $this->consumer->status === ConsumerStatus::SETTLED
                    ? 'complete-payment'
                    : 'complete-payment-setup',
                true
            );

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('daily')->error('Payment setup failed', [
                'consumer' => $this->consumer,
                'merchants' => $this->merchants,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            $this->error(__('Invalid payment details, please try again.'));
        }
    }

    public function render(): View
    {
        $negotiation = $this->consumer->consumerNegotiation;
        $accountBalance = $this->discountService->fetchAmountToPayWhenPif($this->consumer);

        $title = $negotiation->negotiation_type === NegotiationType::PIF
            ? __('Make Payment')
            : __('Payment Setup');

        $minimumPifDiscountedAmount = $negotiation->counter_one_time_amount
            ?? $negotiation->one_time_settlement
            ?? $accountBalance['discount'];

        $customContentService = app(CustomContentService::class);
        $installments = $this->discountService->fetchInstallmentDetails($this->consumer);
        $savedCards = $this->consumer->savedCards()->get();

        return view('livewire.consumer.payment')->with([
            'minimumPifDiscountedAmount' => $minimumPifDiscountedAmount,
            'installmentDetails' => $this->installmentDetails(),
            'termsAndCondition' => $customContentService->findByCompanyOrSubclient(
                $this->consumer->company_id,
                $this->consumer->subclient_id
            )?->content ?? '',
            'creditorDetails' => $this->setCreditorDetails($this->consumer),
            'consumerNegotiation' => $negotiation,
            'accountBalance' => $accountBalance,
            'installments' => $installments,
            'savedCards' => $savedCards

        ])->title($title);
    }

    protected function saveCard($formData)
    {
        try {
            $this->consumer->savedCards()->create([
                'consumer_id'         => $this->consumer->id,
                'last4digit'          => substr(preg_replace('/\D/', '', $formData['card_number']), -4),
                'card_holder_name'    => $formData['card_holder_name'],
                'expiry'              => $formData['expiry'],
                'encrypted_card_data' => encrypt($formData['card_number']),
            ]);
        } catch (\Exception $e) {
            $this->error(__($e->getMessage()));
        }
    }

    public function getDecryptedCardNumber($encryptedCardData)
    {
        return decrypt($encryptedCardData);  // Decrypt card data using Laravel's Crypt facade
    }


    public function deleteCard($cardId)
    {
        try {
            $card = $this->consumer->savedCards()->findOrFail($cardId);
            $card->delete();
            $this->success(__('Card deleted successfully.'));
        } catch (\Exception $e) {
            $this->error(__('Failed to delete the card.'));
        }
    }
}
