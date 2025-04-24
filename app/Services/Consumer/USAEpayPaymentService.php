<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ExternalPaymentProfile;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Transaction;
use App\Services\ScheduleTransactionService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;

class USAEpayPaymentService extends SchedulePlanPaymentService
{
    public function payRemainingAmount(
        Merchant $merchant,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        Collection $scheduleTransactions,
    ): bool {
        $totalAmounts = $scheduleTransactions->sum('amount');

        try {
            /** @var PaymentProfile $paymentProfile */
            $paymentProfile = $consumer->paymentProfile;

            /** @var MerchantType $paymentMethod */
            $paymentMethod = $paymentProfile->method;

            $response = $this->proceedPayment(
                profileId: $paymentProfile->profile_id,
                transactionKey: $merchant->usaepay_key,
                transactionPin: $merchant->usaepay_pin,
                paymentMethod: $paymentMethod,
                amount: $totalAmounts,
            );

            if ($response) {
                $transactionId = $response->RefNum;
                if ($response->ResultCode === 'A') {
                    $this->successfulTransactionOfRemainingBalance(
                        transactionId: $transactionId,
                        transactionResponse: $response,
                        scheduleTransactions: $scheduleTransactions,
                        consumer: $consumer,
                        consumerNegotiation: $consumerNegotiation,
                        statusCode: $response->ResultCode
                    );

                    return true;
                }

                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response,
                    transactionType: TransactionType::PARTIAL_PIF,
                );

                $transaction->status_code = $response->ResultCode;
                $transaction->amount = $totalAmounts;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay remaining balance of usa_epay', [
                'consumer' => $consumer->id,
                'payment_profile' => $consumer->paymentProfile?->id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    public function payInstallment(
        ScheduleTransaction $scheduleTransaction,
        Merchant $merchant,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation
    ): bool {
        $amount = (float) $scheduleTransaction->amount;

        /** @var PaymentProfile $paymentProfile */
        $paymentProfile = $consumer->paymentProfile;

        /** @var MerchantType $paymentMethod */
        $paymentMethod = $paymentProfile->method;

        try {
            $response = $this->proceedPayment(
                profileId: $paymentProfile->profile_id,
                transactionKey: $merchant->usaepay_key,
                transactionPin: $merchant->usaepay_pin,
                paymentMethod: $paymentMethod,
                amount: $amount,
            );

            if ($response) {
                $transactionId = $response->RefNum;
                if ($response->ResultCode === 'A') {
                    $this->successfulTransactionOfInstallment(
                        transactionId: $transactionId,
                        transactionResponse: $response,
                        consumer: $consumer,
                        consumerNegotiation: $consumerNegotiation,
                        scheduleTransaction: $scheduleTransaction,
                        statusCode: $response->ResultCode,
                    );

                    return true;
                }

                $transaction = $this->failedTransaction(
                    transactionId: null,
                    consumer: $consumer,
                    transactionResponse: $response,
                    transactionType: TransactionType::INSTALLMENT,
                );

                $transaction->status_code = $response->ResultCode;
                $transaction->amount = $amount;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay installment of usa_epay', [
                'consumer' => $consumer->id,
                'payment_profile' => $consumer->paymentProfile?->id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    public function proceedPayment(
        ?string $profileId,
        ?string $transactionKey,
        ?string $transactionPin,
        MerchantType $paymentMethod,
        float $amount,
    ): mixed {
        $client = new SoapClient(config('services.usaepay_url'));
        $token = $this->getToken($transactionKey, $transactionPin);

        $parameters = [
            'Command' => $paymentMethod === MerchantType::CC ? 'Sale' : 'Check',
            'Details' => [
                'Invoice' => rand(1232132, 9999999),
                'PONum' => null,
                'OrderID' => null,
                'Description' => 'Younegotiate consumer payment for remaining balance or installment',
                'Amount' => $amount,
            ],
        ];

        $paymentMethods = $client->getCustomerPaymentMethods($token, $profileId);

        return $client->runCustomerTransaction($token, $profileId, $paymentMethods[0]->MethodID, $parameters);
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
    public function createPaymentProfile(array $data, Consumer $consumer, Merchant $merchant): mixed
    {
        $client = new SoapClient(config('services.usaepay_url'));

        try {
            $token = $this->getToken($merchant->usaepay_key, $merchant->usaepay_pin);

            $gatewayData = [
                'BillingAddress' => [
                    'FirstName' => $data['first_name'],
                    'LastName' => $data['last_name'],
                    'Company' => $consumer->company->company_name,
                    'Street' => $data['address'],
                    'Street2' => null,
                    'City' => $data['city'],
                    'State' => $data['state'],
                    'Zip' => $data['zip'],
                    'Country' => 'US',
                    'email' => $consumer->email1,
                    'Phone' => $consumer->mobile1,
                    'Fax' => '999-999-9999',
                ],
                'CustomerID' => $consumer->id,
                'Description' => $consumer->company->company_name . ' Billing Via YouNegotiate',
                'Enabled' => false,
                'Amount' => $consumer->total_balance,
                'Tax' => '0',
                'Next' => '',
                'Notes' => $consumer->company->company_name . ' Billing Via YouNegotiate',
                'NumLeft' => '50',
                'OrderID' => rand(),
                'ReceiptNote' => 'Created Charge',
                'SendReceipt' => true,
                'Source' => $consumer->company->company_name,
                'Schedule' => '',
                'User' => '',
            ];

            if ($data['method'] === MerchantType::CC->value) {
                $gatewayData['PaymentMethods'] = [
                    [
                        'MethodType' => 'CreditCard',
                        'CardNumber' => $data['card_number'],
                        'CardExpiration' => Str::replace('/', '', $data['expiry']),
                        'MethodName' => 'Personal Checking',
                        'SecondarySort' => 1,
                        'Expires' => '',
                    ],
                ];
            }

            if ($data['method'] === MerchantType::ACH->value) {
                $gatewayData['PaymentMethods'] = [
                    [
                        'Account' => $data['account_number'],
                        'AccountType' => Str::ucfirst($data['account_type']),
                        'Routing' => $data['routing_number'],
                        'MethodName' => 'ACH',
                        'SecondarySort' => 1,
                    ],
                ];
            }

            return $client->addCustomer($token, $gatewayData);
        } catch (SoapFault $exception) {
            Log::channel('daily')->error('USAEpay payment profile creation failed.', [
                'message' => $exception->getMessage(),
                'Request' => $client->__getLastRequest(),
                'Response' => $client->__getLastResponse(),
            ]);

            throw new Exception('Failed usaepay merchant creation');
        }
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
     *  amount: float,
     *  account_number: string,
     *  account_type: string,
     *  routing_number: string,
     *  is_pif: bool
     * } $data
     */
    public function makePayment(ExternalPaymentProfile $externalPaymentProfile, array $data, Merchant $merchant)
    {
        $paymentProfileId = $this->createPaymentProfile($data, $externalPaymentProfile->consumer, $merchant);

        if ($paymentProfileId) {
            $externalPaymentProfile->update([
                'payment_profile_id' => $externalPaymentProfile->id,
            ]);

            $response = $this->proceedPayment(
                (string) $paymentProfileId,
                $merchant->usaepay_key,
                $merchant->usaepay_pin,
                MerchantType::tryFrom($data['method']),
                (float) $data['amount']
            );

            if ($response->ResultCode === 'A' && $response->Result) {
                $shares = $data['is_pif']
                    ? app(CompanyMembershipService::class)->fetchShares($externalPaymentProfile->consumer, (float) $data['amount'])
                    : app(ScheduleTransactionService::class)->calculateShareAmount($externalPaymentProfile->consumer, (float) $data['amount']);

                $transaction = Transaction::query()->create([
                    'company_id' => $externalPaymentProfile->company_id,
                    'subclient_id' => $externalPaymentProfile->subclient_id,
                    'consumer_id' => $externalPaymentProfile->consumer_id,
                    'external_payment_profile_id' => $externalPaymentProfile->id,
                    'transaction_id' => $response->RefNum,
                    'transaction_type' => $data['is_pif'] ? TransactionType::PIF : TransactionType::PARTIAL_PIF,
                    'status' => TransactionStatus::SUCCESSFUL,
                    'amount' => number_format((float) $data['amount'], 2, thousands_separator: ''),
                    'status_code' => $response->ResultCode,
                    'gateway_response' => (array) $response,
                    'rnn_share' => $shares['yn_share'],
                    'company_share' => $shares['company_share'],
                    'revenue_share_percentage' => $shares['share_percentage'],
                    'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                    'payment_mode' => $data['method'],
                    'superadmin_process' => 0,
                ]);

                return $transaction->id;
            } else {
                Log::channel('daily')->error('Failed usaepay merchant payment for donation', [
                    'consumer' => $externalPaymentProfile->consumer,
                    'merchant' => $merchant,
                    'data' => $data,
                ]);

                throw new Exception('Failed usaepay merchant payment for donation');
            }
        }

        Log::channel('daily')->error('Failed usaepay merchant payment for donation', [
            'consumer' => $externalPaymentProfile->consumer,
            'merchant' => $merchant,
            'data' => $data,
        ]);

        throw new Exception('Failed usaepay merchant payment for donation');
    }

    /**
     * @return array<string, mixed>
     */
    private function getToken(?string $transactionKey, ?string $transactionPin): array
    {
        $seed = time() . rand();

        return [
            'SourceKey' => $transactionKey,
            'PinHash' => [
                'Type' => 'sha1',
                'Seed' => $seed,
                'HashValue' => sha1($transactionKey . $seed . $transactionPin),
            ],
            'ClientIP' => null,
        ];
    }
}
