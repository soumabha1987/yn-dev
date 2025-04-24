<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Consumer\Forms\PaymentForm;
use App\Livewire\Consumer\Traits\Agreement;
use App\Models\Consumer;
use App\Models\ExternalPaymentProfile;
use App\Models\Merchant;
use App\Models\ScheduleTransaction;
use App\Services\Consumer\AuthorizePaymentService;
use App\Services\Consumer\ConsumerService;
use App\Services\Consumer\DiscountService;
use App\Services\Consumer\MerchantService;
use App\Services\Consumer\SchedulePlanPaymentService;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\Consumer\StripePaymentService;
use App\Services\Consumer\TilledPaymentService;
use App\Services\Consumer\USAEpayPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.consumer.app-layout')]
class ExternalPayment extends Component
{
    // This is not used for download Agreement but,
    // It is used only for the `negotiateAmount` functions.
    use Agreement;

    #[Url('c')]
    public string $consumerId = '';

    public PaymentForm $form;

    public Consumer $consumer;

    public $amount = '';

    public float $totalPayableAmount = 0.00;

    public bool $paymentIsSuccessful = false;

    public bool $isDisplayName = true;

    public bool $amountIsEditable = false;

    public Collection $merchants;

    public Collection $scheduleTransactions;

    public ExternalPaymentProfile $externalPaymentProfile;

    protected ConsumerService $consumerService;

    protected ScheduleTransactionService $scheduleTransactionService;

    protected DiscountService $discountService;

    public function __construct()
    {
        $this->consumerService = app(ConsumerService::class);
        $this->scheduleTransactionService = app(ScheduleTransactionService::class);
        $this->discountService = app(DiscountService::class);
    }

    public function boot(): void
    {
        $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($validator->errors()->has('amount')) {
                    $this->dispatch('scroll-into-amount');
                }
            });
        });

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
        $this->consumer = $this->consumerService->fetchById((int) hex2bin($this->consumerId));
        $this->amountIsEditable = $this->consumer->payment_setup === true && $this->consumer->status === ConsumerStatus::PAYMENT_ACCEPTED;

        $this->consumer->loadMissing('consumerNegotiation');

        $consumerNegotiation = $this->consumer->consumerNegotiation;

        $totalPayableAmount = match (true) {
            $this->amountIsEditable => (float) $this->scheduleTransactionService->fetchByConsumerExternalPayments($this->consumer->id)->sum('amount'),
            $consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => (float) $consumerNegotiation->one_time_settlement,
            $consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => (float) $consumerNegotiation->counter_one_time_amount,
            default => $this->discountService->fetchAmountToPayWhenPif($this->consumer)['discount']
        };

        $this->totalPayableAmount = (float) number_format((float) $totalPayableAmount, 2, thousands_separator: '');

        $this->merchants = app(MerchantService::class)->getMerchant($this->consumer);
    }

    public function makePayment(): void
    {
        if ($this->amountIsEditable) {
            $validatedData = $this->validate(['amount' => ['required', 'numeric', 'gte:1']]);

            $validatedData += $this->form->validate();
            $validatedData['is_pif'] = false;

            // We sent the payment link to multiple people.Therefore, we need to check if
            // someone else is making a payment at the same time. We do not allow
            // anyone to make a payment greater than the total payable amount.
            $this->scheduleTransactions = $this->scheduleTransactionService->fetchByConsumerExternalPayments($this->consumer->id);
            $this->totalPayableAmount = (float) $this->scheduleTransactions->sum('amount');

            if ($this->totalPayableAmount < $validatedData['amount']) {
                throw ValidationException::withMessages([
                    'amount' => __('validation.lte.numeric', ['attribute' => 'amount', 'value' => $this->totalPayableAmount]),
                ]);
            }
        } else {
            $validatedData = $this->form->validate();

            // We sent the payment link to multiple people.Therefore, we need to check if
            // someone else is making a payment at the same time. We do not allow
            // anyone to make a payment greater than the total payable amount.
            $consumerNegotiation = $this->consumer->consumerNegotiation;

            $validatedData['amount'] = match (true) {
                $consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiation->offer_accepted => (float) $consumerNegotiation->one_time_settlement,
                $consumerNegotiation?->negotiation_type === NegotiationType::PIF && $consumerNegotiation->counter_offer_accepted => (float) $consumerNegotiation->counter_one_time_amount,
                default => $this->discountService->fetchAmountToPayWhenPif($this->consumer)['discount']
            };

            $validatedData['is_pif'] = true;
        }

        $validatedData['amount'] = (float) number_format((float) $validatedData['amount'], 2, thousands_separator: '');

        /** @var Merchant $merchant */
        $merchant = $this->merchants->firstWhere('merchant_type', MerchantType::tryFrom($validatedData['method']));

        $this->createExternalPaymentProfile($validatedData);

        try {
            $transactionId = match ($merchant->merchant_name) {
                MerchantName::STRIPE => app(StripePaymentService::class)->makePayment($this->externalPaymentProfile, $validatedData, $merchant),
                MerchantName::AUTHORIZE => app(AuthorizePaymentService::class)->makePayment($this->externalPaymentProfile, $validatedData, $merchant),
                MerchantName::USA_EPAY => app(USAEpayPaymentService::class)->makePayment($this->externalPaymentProfile, $validatedData, $merchant),
                MerchantName::YOU_NEGOTIATE => app(TilledPaymentService::class)->makePayment($this->externalPaymentProfile, $validatedData, $merchant),
            };

            $validatedData['is_pif']
                ? $this->deleteScheduleTransactionsWithUpdateConsumer($validatedData['amount'])
                : $this->calculateScheduleTransactions($validatedData, (int) $transactionId);

            TriggerEmailAndSmsServiceJob::dispatch($this->consumer, match (true) {
                $this->totalPayableAmount === $validatedData['amount'] && $this->amountIsEditable => CommunicationCode::HELPING_HAND_PAY_FULL_CURRENT_BALANCE,
                $this->totalPayableAmount === $validatedData['amount'] && ! $this->amountIsEditable => CommunicationCode::HELPING_HAND_FULL_PAY_SETTLED,
                default => CommunicationCode::HELPING_HAND_SUCCESSFUL_PAYMENT,
            });

            $this->js('$confetti()');

            $this->paymentIsSuccessful = true;
        } catch (Exception $exception) {
            Log::channel('daily')->error('External payment failed', [
                'consumer' => $this->consumer,
                'amount' => $validatedData['amount'],
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            $this->error(__('Invalid payment details, please try again.'));
        }
    }

    protected function calculateScheduleTransactions(array $data, int $transactionId): void
    {
        $amount = $data['amount'];

        app(SchedulePlanPaymentService::class)->updateConsumerNegotiation($this->consumer->consumerNegotiation, (float) $amount);

        $this->consumer->update([
            'current_balance' => max(0, $this->consumer->current_balance - $amount),
            'has_failed_payment' => false,
            'status' => $this->totalPayableAmount === $data['amount'] ? ConsumerStatus::SETTLED : $this->consumer->status,
        ]);

        $this->scheduleTransactions->each(
            function (ScheduleTransaction $scheduleTransaction) use (&$amount, $transactionId): bool {
                if ($amount > 0) {
                    if ($scheduleTransaction->amount <= $amount) {
                        $amount = $amount - $scheduleTransaction->amount;
                        $scheduleTransaction->update([
                            'status' => TransactionStatus::SUCCESSFUL,
                            'external_payment_profile_id' => $this->externalPaymentProfile->id,
                            'transaction_id' => $transactionId,
                        ]);

                        return true;
                    }

                    $scheduleTransactionAmount = $scheduleTransaction->amount;
                    $scheduleTransaction->update([
                        'amount' => $scheduleTransaction->amount -= $amount,
                        'status' => ($scheduleTransaction->amount -= $amount > 0)
                            ? TransactionStatus::SCHEDULED
                            : TransactionStatus::SUCCESSFUL,
                        'external_payment_profile_id' => $this->externalPaymentProfile->id,
                        'transaction_id' => ($scheduleTransaction->amount -= $amount > 0) ? null : $transactionId,
                    ]);

                    $amount = max(0, $amount - $scheduleTransactionAmount);

                    return true;
                }

                return false;
            }
        );
    }

    private function deleteScheduleTransactionsWithUpdateConsumer(float $paidAmount): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::SETTLED,
            'has_failed_payment' => false,
            'offer_accepted' => true,
            'current_balance' => max(0, $this->consumer->current_balance - $paidAmount),
        ]);

        if ($this->consumer->consumerNegotiation) {
            $this->consumer->consumerNegotiation->update([
                'payment_plan_current_balance' => 0,
            ]);
        }

        $this->scheduleTransactionService->deleteScheduled($this->consumer->id);
    }

    protected function createExternalPaymentProfile(array $data): void
    {
        $accountData = [
            'last_four_digit' => filled($data['card_number']) ? Str::substr($data['card_number'], -4) : null,
            'expiry' => filled($data['expiry']) ? $data['expiry'] : null,
            'account_number' => filled($data['account_number']) ? Str::substr($data['account_number'], -2) : null,
            'routing_number' => filled($data['routing_number']) ? $data['routing_number'] : null,
        ];

        if ($this->merchants->containsStrict('merchant_name', MerchantName::YOU_NEGOTIATE)) {
            $accountData = [
                'last_four_digit' => data_get($data, 'tilled_response.card.last4'),
                'expiry' => data_get($data, 'method') === MerchantType::CC->value ? data_get($data, 'tilled_response.card.exp_month') . '/' . data_get($data, 'tilled_response.card.exp_year') : null,
                'account_number' => data_get($data, 'tilled_response.ach_debit.last2'),
                'routing_number' => data_get($data, 'tilled_response.ach_debit.routing_number'),
                'payment_profile_id' => data_get($data, 'payment_method_id'),
            ];
        }

        $this->externalPaymentProfile = ExternalPaymentProfile::query()->create([
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'consumer_id' => $this->consumer->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'method' => $data['method'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            ...$accountData,
        ]);
    }

    public function downloadReceipt(): StreamedResponse
    {
        $this->consumer->loadMissing(['company', 'subclient', 'consumerProfile']);

        $this->externalPaymentProfile->loadMissing(['transaction', 'scheduleTransaction']);

        $pdf = Pdf::setOption('isRemoteEnabled', true)
            ->loadView('pdf.consumer.receipt', [
                'externalPaymentProfile' => $this->externalPaymentProfile,
                'consumer' => $this->consumer,
            ])
            ->output();

        $this->dispatch('dont-close-dialog');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, 'you_negotiate_receipt.pdf');
    }

    public function render(): View
    {
        return view('livewire.consumer.external-payment')
            ->with(
                'monthlyPayableAmount',
                $this->consumer->consumerNegotiation?->negotiation_type === NegotiationType::INSTALLMENT
                ? $this->consumer->consumerNegotiation->monthly_amount
                : 0.00
            )
            ->title(__('Donate to friends or family'));
    }
}
