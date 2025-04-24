<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits\Payments;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\MembershipTransactionStatus;
use App\Enums\MerchantType;
use App\Enums\NegotiationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\StripePaymentDetail;
use App\Models\Transaction;
use App\Models\YnTransaction;
use App\Services\CompanyMembershipService;
use App\Services\Consumer\AuthorizePaymentService;
use App\Services\Consumer\ScheduleTransactionService;
use App\Services\Consumer\StripePaymentService;
use App\Services\Consumer\TilledPaymentService;
use App\Services\Consumer\USAEpayPaymentService;
use App\Services\PartnerService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;

/**
 * @property Consumer $consumer
 * @property Collection<Merchant> $merchants
 *
 * @method array{
 *  user_is_come_for_pif_payment: bool,
 *  is_pif_offer_only: bool,
 *  first_payment_date: ?Carbon,
 *  settlement_amount: float,
 *  minimum_pif_discounted_amount: float
 * } userIsComeForPifPayment()
 */
trait PaymentProfiles
{
    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  method: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  account_number: string,
     *  account_type: string,
     *  routing_number: string
     * } $data
     */
    private function createOrUpdateAuthorizeCustomerProfile(array $data): void
    {
        $authorizePaymentService = app(AuthorizePaymentService::class);

        $response = $authorizePaymentService->createPaymentProfile($data, $this->consumer, $this->merchants->first());

        if ($response->getMessages()->getResultCode() === 'Ok') {
            $paymentProfile = PaymentProfile::query()->create([
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'merchant_id' => $this->merchants->first()->id,
                'method' => data_get($data, 'method'),
                'expirity' => data_get($data, 'expiry'),
                'fname' => $data['first_name'],
                'lname' => $data['last_name'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'last4digit' => data_get($data, 'card_number') ? Str::substr($data['card_number'], -4) : null,
                'account_number' => data_get($data, 'account_number') ? Str::substr($data['account_number'], -2) : null,
                'routing_number' => data_get($data, 'routing_number'),
                'profile_id' => $response->getCustomerProfileId(),
                'payment_profile_id' => $response->getCustomerPaymentProfileIdList()[0],
                'shipping_profile_id' => $response->getCustomerShippingAddressIdList()[0],
            ]);

            $isPifNegotiation = $this->userIsComeForPifPayment();

            if ($isPifNegotiation['user_is_come_for_pif_payment']) {
                $response = $authorizePaymentService->proceedPayment(
                    $this->merchants->first()->authorize_login_id,
                    $this->merchants->first()->authorize_transaction_key,
                    $isPifNegotiation['settlement_amount'],
                    $paymentProfile->profile_id,
                    $paymentProfile->payment_profile_id,
                );

                $transactionResponse = $response->getTransactionResponse();

                if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse !== null && $transactionResponse->getMessages() !== null) {
                    $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

                    $settlementAmount = $isPifNegotiation['settlement_amount'];
                    $ynShare = number_format(($settlementAmount * $revenueShareFee / 100), 2, thousands_separator: '');
                    $companyShare = number_format(($settlementAmount - $ynShare), 2, thousands_separator: '');

                    $transaction = Transaction::query()->create([
                        'transaction_type' => TransactionType::PIF,
                        'transaction_id' => $transactionResponse?->getTransId(),
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'gateway_response' => $transactionResponse,
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'revenue_share_percentage' => $revenueShareFee,
                        'rnn_share' => $ynShare,
                        'company_share' => $companyShare,
                        'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                        'payment_mode' => $data['method'],
                        'superadmin_process' => 0,
                    ]);

                    ScheduleTransaction::query()->create([
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'transaction_id' => $transaction->id,
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'transaction_type' => TransactionType::PIF,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'revenue_share_percentage' => $revenueShareFee,
                        'schedule_date' => now()->toDateString(),
                        'schedule_time' => now()->toTimeString(),
                        'attempt_count' => 1,
                        'last_attempted_at' => now(),
                    ]);

                    $this->consumer->update([
                        'status' => ConsumerStatus::SETTLED,
                        'has_failed_payment' => false,
                        'offer_accepted' => true,
                        'current_balance' => max(0, (float) $this->consumer->current_balance - $isPifNegotiation['settlement_amount']),
                    ]);

                    $authorizePaymentService->updateConsumerNegotiation($this->consumer->consumerNegotiation, $isPifNegotiation['settlement_amount']);
                    $this->consumer->consumerNegotiation->update([
                        'offer_accepted' => true,
                        'approved_by' => 'Auto',
                    ]);

                    TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::BALANCE_PAID);

                    $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer->id]);

                    return;
                }

                if ($transactionResponse->getErrors() !== null) {
                    Log::channel('daily')->error('Failed Authorize Payment', [
                        'status code' => $transactionResponse->getErrors()[0]->getErrorCode(),
                        'Stack Trace' => $transactionResponse->getErrors(),
                    ]);

                    throw new Exception('Failed Authorize Payment');
                }
            }

            if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
                app(ScheduleTransactionService::class)
                    ->createInstallmentsIfNotCreated(
                        $this->consumer,
                        $this->consumer->consumerNegotiation,
                        $paymentProfile
                    );

                if (! $this->consumer->offer_accepted) {
                    $this->redirectRoute('consumer.account');

                    return;
                }

                $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                return;
            } else {
                app(ScheduleTransactionService::class)->createScheduledPifIfNotCreated(
                    $this->consumer,
                    $this->consumer->consumerNegotiation,
                    $paymentProfile
                );

                if ($this->consumer->consumerNegotiation->counter_one_time_amount) {
                    $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                    return;
                }

                $this->redirectRoute('consumer.account');

                return;
            }
        }

        throw new Exception('Failed Authorize Payment');
    }

    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  method: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  account_number: string,
     *  account_type: string,
     *  routing_number: string
     * } $data
     */
    private function createOrUpdateUsaEpayCustomerProfile(array $data): void
    {
        $usaEpayPaymentService = app(USAEpayPaymentService::class);

        $paymentProfileId = $usaEpayPaymentService->createPaymentProfile($data, $this->consumer, $this->merchants->first());

        if ($paymentProfileId) {
            $paymentProfile = PaymentProfile::query()->create([
                'consumer_id' => $this->consumer->id,
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'merchant_id' => $this->merchants->first()->id,
                'method' => data_get($data, 'method'),
                'expirity' => data_get($data, 'expiry'),
                'fname' => $data['first_name'],
                'lname' => $data['last_name'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'last4digit' => data_get($data, 'card_number') ? Str::substr($data['card_number'], -4) : null,
                'account_number' => data_get($data, 'account_number') ? Str::substr($data['account_number'], -2) : null,
                'routing_number' => data_get($data, 'routing_number'),
                'profile_id' => $paymentProfileId,
            ]);

            $isPifNegotiation = $this->userIsComeForPifPayment();

            if ($isPifNegotiation['user_is_come_for_pif_payment']) {
                $response = $usaEpayPaymentService->proceedPayment(
                    (string) $paymentProfile->profile_id,
                    $this->merchants->first()->usaepay_key,
                    $this->merchants->first()->usaepay_pin,
                    MerchantType::tryFrom($data['method']),
                    $isPifNegotiation['settlement_amount']
                );

                if ($response->ResultCode === 'A' && $response->Result) {
                    $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

                    $settlementAmount = $isPifNegotiation['settlement_amount'];

                    $ynShare = number_format(($settlementAmount * $revenueShareFee / 100), 2, thousands_separator: '');
                    $companyShare = number_format(($settlementAmount - $ynShare), 2, thousands_separator: '');

                    $transaction = Transaction::query()->create([
                        'transaction_type' => TransactionType::PIF,
                        'transaction_id' => $response->RefNum,
                        'subclient_id' => $this->consumer->subclient_id,
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'gateway_response' => (array) $response,
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'revenue_share_percentage' => $revenueShareFee,
                        'rnn_share' => $ynShare,
                        'company_share' => $companyShare,
                        'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                        'payment_mode' => $data['method'],
                        'superadmin_process' => 0,
                    ]);

                    ScheduleTransaction::query()->create([
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'transaction_id' => $transaction->id,
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'transaction_type' => TransactionType::PIF,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'revenue_share_percentage' => $revenueShareFee,
                        'schedule_date' => now()->toDateString(),
                        'schedule_time' => now()->toTimeString(),
                        'attempt_count' => 1,
                        'last_attempted_at' => now(),
                    ]);

                    $this->consumer->update([
                        'status' => ConsumerStatus::SETTLED,
                        'has_failed_payment' => false,
                        'offer_accepted' => true,
                        'current_balance' => max(0, ((float) $this->consumer->current_balance) - $isPifNegotiation['settlement_amount']),
                    ]);

                    $usaEpayPaymentService->updateConsumerNegotiation($this->consumer->consumerNegotiation, $isPifNegotiation['settlement_amount']);
                    $this->consumer->consumerNegotiation->update([
                        'offer_accepted' => true,
                        'approved_by' => 'Auto',
                    ]);

                    TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::BALANCE_PAID);

                    $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer->id]);

                    return;
                } else {
                    throw new Exception('Failed USAEpay Payment.');
                }
            }

            if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
                app(ScheduleTransactionService::class)->createInstallmentsIfNotCreated(
                    $this->consumer,
                    $this->consumer->consumerNegotiation,
                    $paymentProfile
                );

                if (! $this->consumer->offer_accepted) {
                    $this->redirectRoute('consumer.account');

                    return;
                }

                $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                return;
            } else {
                app(ScheduleTransactionService::class)->createScheduledPifIfNotCreated(
                    $this->consumer,
                    $this->consumer->consumerNegotiation,
                    $paymentProfile
                );

                if ($this->consumer->consumerNegotiation->counter_one_time_amount) {
                    $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                    return;
                }

                $this->redirectRoute('consumer.account');

                return;
            }
        }

        throw new Exception('Failed USAEpay Payment');
    }

    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  method: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  account_number: string,
     *  account_type: string,
     *  routing_number: string
     * } $data
     *
     * @throws Exception
     */
    private function createOrUpdateStripeCustomerProfile(array $data): void
    {
        $stripePaymentService = app(StripePaymentService::class);

        $paymentProfile = PaymentProfile::query()->create([
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'subclient_id' => $this->consumer->subclient_id,
            'merchant_id' => $this->merchants->first()->id,
            'method' => data_get($data, 'method'),
            'expirity' => data_get($data, 'expiry'),
            'fname' => $data['first_name'],
            'lname' => $data['last_name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'last4digit' => data_get($data, 'card_number') ? Str::substr($data['card_number'], -4) : null,
            'account_number' => data_get($data, 'account_number') ? Str::substr($data['account_number'], -2) : null,
            'routing_number' => data_get($data, 'routing_number'),
        ]);

        $stripePaymentService->createOrUpdateCustomerProfile($data, $this->merchants->first(), $paymentProfile);

        $isPifNegotiation = $this->userIsComeForPifPayment();
        $settlementAmount = $isPifNegotiation['settlement_amount'];

        if ($this->userIsComeForPifPayment()['user_is_come_for_pif_payment']) {
            $paymentIntent = $stripePaymentService->proceedPayment(
                $this->merchants->first()->stripe_secret_key,
                StripePaymentDetail::query()->where('consumer_id', $paymentProfile->consumer_id)->first(),
                $this->consumer,
                $settlementAmount,
            );

            if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

                $ynShare = number_format(($settlementAmount * $revenueShareFee / 100), 2, thousands_separator: '');
                $companyShare = number_format(($settlementAmount - $ynShare), 2, thousands_separator: '');

                Transaction::query()->create([
                    'transaction_type' => TransactionType::PIF,
                    'transaction_id' => $paymentIntent->id,
                    'subclient_id' => $this->consumer->subclient_id,
                    'consumer_id' => $this->consumer->id,
                    'company_id' => $this->consumer->company_id,
                    'payment_profile_id' => $paymentProfile->id,
                    'status' => TransactionStatus::SUCCESSFUL,
                    'gateway_response' => $paymentIntent->toArray(),
                    'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                    'revenue_share_percentage' => $revenueShareFee,
                    'rnn_share' => $ynShare,
                    'company_share' => $companyShare,
                    'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                    'payment_mode' => $data['method'],
                    'superadmin_process' => 0,
                ]);

                $this->consumer->update([
                    'status' => ConsumerStatus::SETTLED,
                    'has_failed_payment' => false,
                    'offer_accepted' => true,
                    'current_balance' => max(0, (float) $this->consumer->current_balance - $settlementAmount),
                ]);

                $stripePaymentService->updateConsumerNegotiation($this->consumer->consumerNegotiation, $settlementAmount);
                $this->consumer->consumerNegotiation->update([
                    'offer_accepted' => true,
                    'approved_by' => 'Auto',
                ]);

                TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::BALANCE_PAID);

                $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer->id]);

                return;
            }

            if ($paymentIntent->status === PaymentIntent::STATUS_CANCELED) {
                Log::channel('daily')->error('External payment failed of stripe merchant', [
                    'data' => $data,
                    'consumer_id' => $paymentProfile->consumer_id,
                    'merchant' => $paymentProfile->merchant,
                ]);

                throw new Exception('Invalid payment details, please try again.');
            }

            Log::channel('daily')->error('External payment failed of stripe merchant', [
                'data' => $data,
                'consumer_id' => $paymentProfile->consumer_id,
                'merchant' => $paymentProfile->merchant,
            ]);

            throw new Exception('Invalid payment details, please try again.');
        }

        if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT) {
            app(ScheduleTransactionService::class)->createInstallmentsIfNotCreated(
                $this->consumer,
                $this->consumer->consumerNegotiation,
                $paymentProfile
            );
        }

        if ($this->userIsComeForPifPayment()['is_pif_offer_only']) {
            app(ScheduleTransactionService::class)->createScheduledPifIfNotCreated(
                $this->consumer,
                $this->consumer->consumerNegotiation,
                $paymentProfile
            );

            if (! $this->consumer->consumerNegotiation->offer_accepted) {
                $this->redirectRoute('consumer.account');

                return;
            }
        }

        $this->consumer->update(['payment_setup' => true]);

        if ($this->consumer->consumerNegotiation->counter_one_time_amount) {
            $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

            return;
        }

        $this->redirectRoute('consumer.account');
    }

    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  method: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  account_number: string,
     *  account_type: string,
     *  routing_number: string,
     *  tilled_response: array,
     *  payment_method_id: string
     * } $data
     */
    private function createOrUpdateTilledCustomerProfile(array $data): void
    {
        $this->consumer->loadMissing(['company.partner', 'subclient']);

        $tilledMerchantAccountId = $this->consumer->subclient?->tilled_merchant_account_id ?? $this->consumer->company->tilled_merchant_account_id;

        $isPifNegotiation = $this->userIsComeForPifPayment();

        if ($isPifNegotiation['user_is_come_for_pif_payment']) {
            $revenueShareFee = app(CompanyMembershipService::class)->fetchFee($this->consumer);

            $settlementAmount = $isPifNegotiation['settlement_amount'];

            $ynShare = number_format(($settlementAmount * $revenueShareFee / 100), 2, thousands_separator: '');

            $companyShare = number_format(($settlementAmount - $ynShare), 2, thousands_separator: '');

            $response = Http::tilled($tilledMerchantAccountId)
                ->post('payment-intents', [
                    'amount' => intval($settlementAmount * 100),
                    'currency' => 'usd',
                    'payment_method_types' => [$data['method'] === MerchantType::CC->value ? 'card' : 'ach_debit'],
                    'payment_method_id' => $data['payment_method_id'],
                    'confirm' => true,
                    'platform_fee_amount' => intval((float) $ynShare * 100),
                ]);

            if ($response->successful()) {
                if (in_array($response->json('status'), ['processing', 'succeeded'])) {
                    $paymentProfile = PaymentProfile::query()->create([
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'merchant_id' => $this->merchants->first()->id,
                        'method' => data_get($data, 'method'),
                        'expirity' => data_get($data, 'method') === MerchantType::CC->value ? data_get($data, 'tilled_response.card.exp_month') . '/' . data_get($data, 'tilled_response.card.exp_year') : null,
                        'fname' => $data['first_name'],
                        'lname' => $data['last_name'],
                        'address' => $data['address'],
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'zip' => $data['zip'],
                        'last4digit' => data_get($data, 'tilled_response.card.last4'),
                        'account_number' => data_get($data, 'tilled_response.ach_debit.last2'),
                        'routing_number' => data_get($data, 'tilled_response.ach_debit.routing_number'),
                        'profile_id' => data_get($data, 'payment_method_id'),
                        'payment_profile_id' => $response->json('id'),
                    ]);

                    $lastYnTransaction = YnTransaction::query()->latest()->value('rnn_invoice_id');

                    $rnnInvoiceId = $lastYnTransaction ? $lastYnTransaction + 1 : 5000;

                    $partnerRevenueShare = 0;

                    if ($this->consumer->company->partner_id) {
                        $partnerRevenueShare = app(PartnerService::class)
                            ->calculatePartnerRevenueShare($this->consumer->company->partner, (float) $ynShare);
                    }

                    $ynTransaction = YnTransaction::query()->create([
                        'company_id' => $this->consumer->company_id,
                        'amount' => number_format((float) $ynShare, 2, thousands_separator: ''),
                        'response' => $response->json(),
                        'billing_cycle_start' => now(),
                        'billing_cycle_end' => now(),
                        'email_count' => 0,
                        'sms_count' => 0,
                        'phone_no_count' => 0,
                        'email_cost' => 0,
                        'sms_cost' => 0,
                        'rnn_invoice_id' => $rnnInvoiceId,
                        'reference_number' => mt_rand(100000000, 999999999),
                        'status' => MembershipTransactionStatus::SUCCESS->value,
                        'partner_revenue_share' => number_format((float) $partnerRevenueShare, 2, thousands_separator: ''),
                    ]);

                    $transaction = Transaction::query()->create([
                        'transaction_type' => TransactionType::PIF,
                        'transaction_id' => $response->json('id'),
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'gateway_response' => $response->json(),
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                        'payment_mode' => $data['method'],
                        'rnn_share' => $ynShare,
                        'company_share' => $companyShare,
                        'revenue_share_percentage' => $revenueShareFee,
                        'superadmin_process' => 0,
                        'rnn_share_pass' => now(),
                        'yn_transaction_id' => $ynTransaction->id,
                    ]);

                    ScheduleTransaction::query()->create([
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'payment_profile_id' => $paymentProfile->id,
                        'transaction_id' => $transaction->id,
                        'amount' => number_format($settlementAmount, 2, thousands_separator: ''),
                        'revenue_share_percentage' => $revenueShareFee,
                        'transaction_type' => TransactionType::PIF,
                        'status' => TransactionStatus::SUCCESSFUL,
                        'schedule_date' => today()->toDateString(),
                        'schedule_time' => now()->toTimeString(),
                        'attempt_count' => 1,
                        'last_attempted_at' => now(),
                    ]);

                    $this->consumer->update([
                        'status' => ConsumerStatus::SETTLED,
                        'has_failed_payment' => false,
                        'offer_accepted' => true,
                        'current_balance' => max(0, (float) $this->consumer->current_balance - $isPifNegotiation['settlement_amount']),
                    ]);

                    app(TilledPaymentService::class)->updateConsumerNegotiation($this->consumer->consumerNegotiation, $isPifNegotiation['settlement_amount']);
                    $this->consumer->consumerNegotiation->update([
                        'offer_accepted' => true,
                        'approved_by' => 'Auto',
                    ]);

                    TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::BALANCE_PAID);

                    $this->redirectRoute('consumer.complete_payment', ['consumer' => $this->consumer->id]);

                    return;
                } else {
                    throw new Exception('Invalid payment details, please try again.');
                }
            }

            if ($response->failed()) {
                throw new Exception('Invalid payment details, please try again.');
            }
        }

        if (! $isPifNegotiation['user_is_come_for_pif_payment']) {
            $customer = Http::tilled($tilledMerchantAccountId)
                ->post('customers', [
                    'first_name' => $data['first_name'],
                    'middle_name' => '',
                    'last_name' => $data['last_name'],
                    'email' => $this->consumer->email1 ?? '',
                    'phone' => $this->consumer->mobile1 ?? '',
                ]);

            if ($customer->successful()) {
                $attachedConsumer = Http::tilled($tilledMerchantAccountId)
                    ->put("payment-methods/{$data['payment_method_id']}/attach", [
                        'customer_id' => $customer->json('id'),
                    ]);

                if ($attachedConsumer->successful()) {
                    $paymentProfile = PaymentProfile::query()->create([
                        'consumer_id' => $this->consumer->id,
                        'company_id' => $this->consumer->company_id,
                        'subclient_id' => $this->consumer->subclient_id,
                        'merchant_id' => $this->merchants->first()->id,
                        'method' => data_get($data, 'method'),
                        'fname' => $data['first_name'],
                        'lname' => $data['last_name'],
                        'address' => $data['address'],
                        'city' => $data['city'],
                        'state' => $data['state'],
                        'zip' => $data['zip'],
                        'last4digit' => data_get($data, 'tilled_response.card.last4'),
                        'expirity' => data_get($data, 'method') === MerchantType::CC->value ? data_get($data, 'tilled_response.card.exp_month') . '/' . data_get($data, 'tilled_response.card.exp_year') : null,
                        'account_number' => data_get($data, 'tilled_response.ach_debit.last2'),
                        'routing_number' => data_get($data, 'tilled_response.ach_debit.routing_number'),
                        'profile_id' => data_get($data, 'payment_method_id'),
                        'payment_profile_id' => data_get($data, 'tilled_response.id'),
                        'tilled_customer_id' => $customer->json('id'),
                    ]);

                    if ($this->consumer->consumerNegotiation->negotiation_type === NegotiationType::PIF) {
                        app(ScheduleTransactionService::class)->createScheduledPifIfNotCreated(
                            $this->consumer,
                            $this->consumer->consumerNegotiation,
                            $paymentProfile
                        );

                        $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                        return;
                    } else {
                        app(ScheduleTransactionService::class)->createInstallmentsIfNotCreated(
                            $this->consumer,
                            $this->consumer->consumerNegotiation,
                            $paymentProfile
                        );

                        if (! $this->consumer->offer_accepted) {
                            $this->redirectRoute('consumer.account');

                            return;
                        }

                        $this->redirectRoute('consumer.schedule_plan', ['consumer' => $this->consumer->id]);

                        return;
                    }
                }

                throw new Exception('Failed Attaching consumer');
            }

            throw new Exception('Failed Creating Consumer');
        }

        throw new Exception('Failed Tilled Payment');
    }
}
