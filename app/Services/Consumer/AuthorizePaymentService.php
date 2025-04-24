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
use net\authorize\api\contract\v1\BankAccountType;
use net\authorize\api\contract\v1\CreateCustomerProfileRequest;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\CustomerAddressType;
use net\authorize\api\contract\v1\CustomerPaymentProfileType;
use net\authorize\api\contract\v1\CustomerProfilePaymentType;
use net\authorize\api\contract\v1\CustomerProfileType;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\PaymentProfileType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\controller\CreateCustomerProfileController;
use net\authorize\api\controller\CreateTransactionController;

class AuthorizePaymentService extends SchedulePlanPaymentService
{
    public function payRemainingAmount(
        Merchant $merchant,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        Collection $scheduleTransactions,
    ): bool {
        $totalAmounts = $scheduleTransactions->sum('amount');

        try {
            /** @var string $authorizeMerchantLoginId */
            $authorizeMerchantLoginId = $merchant->authorize_login_id;

            /** @var string $authorizeMerchantTransactionKey */
            $authorizeMerchantTransactionKey = $merchant->authorize_transaction_key;

            /** @var PaymentProfile $paymentProfile */
            $paymentProfile = $consumer->paymentProfile;

            /** @var string $profileId */
            $profileId = $paymentProfile->profile_id;

            /** @var string $paymentProfileId */
            $paymentProfileId = $paymentProfile->payment_profile_id;

            $response = $this->proceedPayment(
                authorizeMerchantLoginId: $authorizeMerchantLoginId,
                authorizeMerchantTransactionKey: $authorizeMerchantTransactionKey,
                amount: $totalAmounts,
                consumerProfileId: $profileId,
                paymentProfileId: $paymentProfileId,
            );

            $transactionResponse = $response->getTransactionResponse();
            if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse !== null && $transactionResponse->getMessages() !== null) {
                $this->successfulTransactionOfRemainingBalance(
                    transactionId: $transactionResponse->getTransId(),
                    transactionResponse: $transactionResponse,
                    scheduleTransactions: $scheduleTransactions,
                    consumer: $consumer,
                    consumerNegotiation: $consumerNegotiation,
                    statusCode: (string) $transactionResponse->getResponseCode()
                );

                return true;
            }

            $transaction = $this->failedTransaction(
                transactionId: $transactionResponse?->getTransId(),
                consumer: $consumer,
                transactionResponse: $transactionResponse,
                transactionType: TransactionType::PARTIAL_PIF
            );

            $transaction->status_code = optional($transactionResponse->getErrors(), fn (array $errors) => $errors[0]->getErrorCode());
            $transaction->amount = $totalAmounts;
            $transaction->save();

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay remaining balance of authorize.net', [
                'consumer' => $consumer->id,
                'payment_profile' => $consumer->paymentProfile->id,
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
        ConsumerNegotiation $consumerNegotiation,
    ): bool {
        $amount = (float) $scheduleTransaction->amount;

        try {
            /** @var string $authorizeMerchantLoginId */
            $authorizeMerchantLoginId = $merchant->authorize_login_id;

            /** @var string $authorizeMerchantTransactionKey */
            $authorizeMerchantTransactionKey = $merchant->authorize_transaction_key;

            /** @var PaymentProfile $paymentProfile */
            $paymentProfile = $consumer->paymentProfile;

            /** @var string $profileId */
            $profileId = $paymentProfile->profile_id;

            /** @var string $paymentProfileId */
            $paymentProfileId = $paymentProfile->payment_profile_id;

            $response = $this->proceedPayment(
                authorizeMerchantLoginId: $authorizeMerchantLoginId,
                authorizeMerchantTransactionKey: $authorizeMerchantTransactionKey,
                amount: $amount,
                consumerProfileId: $profileId,
                paymentProfileId: $paymentProfileId,
            );

            $transactionResponse = $response->getTransactionResponse();
            if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse !== null && $transactionResponse->getMessages() !== null) {
                $this->successfulTransactionOfInstallment(
                    transactionId: $transactionResponse?->getTransId(),
                    transactionResponse: $transactionResponse,
                    consumer: $consumer,
                    consumerNegotiation: $consumerNegotiation,
                    scheduleTransaction: $scheduleTransaction,
                    statusCode: (string) $transactionResponse->getResponseCode(),
                );

                return true;
            }

            $transaction = $this->failedTransaction(
                transactionId: $transactionResponse?->getTransId(),
                consumer: $consumer,
                transactionResponse: $transactionResponse,
                transactionType: TransactionType::INSTALLMENT
            );

            $transaction->status_code = optional($transactionResponse->getErrors(), fn (array $errors) => $errors[0]->getErrorCode());
            $transaction->amount = $amount;
            $transaction->save();

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay installment of authorize.net', [
                'consumer' => $consumer->id,
                'payment_profile' => $consumer->paymentProfile->id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
        }
    }

    public function proceedPayment(
        string $authorizeMerchantLoginId,
        string $authorizeMerchantTransactionKey,
        float $amount,
        string $consumerProfileId,
        string $paymentProfileId
    ) {
        $authorizeMerchant = new MerchantAuthenticationType;

        $authorizeMerchant->setName($authorizeMerchantLoginId);
        $authorizeMerchant->setTransactionKey($authorizeMerchantTransactionKey);

        $order = new OrderType;
        $order->setInvoiceNumber((string) mt_rand(100000000, 999999999));
        $order->setDescription('Younegotiate consumer payment for remaining balance or installment');

        $transactionRequestType = new TransactionRequestType;
        $transactionRequestType->setTransactionType('authCaptureTransaction');
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setOrder($order);

        $profileToCharge = new CustomerProfilePaymentType;
        $profileToCharge->setCustomerProfileId($consumerProfileId);
        $paymentProfileType = new PaymentProfileType;
        $paymentProfileType->setPaymentProfileId($paymentProfileId);
        $profileToCharge->setPaymentProfile($paymentProfileType);

        $transactionRequestType->setProfile($profileToCharge);

        $request = new CreateTransactionRequest;
        $request->setMerchantAuthentication($authorizeMerchant);
        $request->setRefId('ref-' . time());
        $request->setTransactionRequest($transactionRequestType);

        $controller = new CreateTransactionController($request);

        return $controller->executeWithApiResponse(config('services.authorize_environment'));
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
    public function createPaymentProfile(array $data, Consumer $consumer, Merchant $merchant)
    {
        $merchantAuthentication = new MerchantAuthenticationType;
        $merchantAuthentication->setName($merchant->authorize_login_id);
        $merchantAuthentication->setTransactionKey($merchant->authorize_transaction_key);

        $paymentType = new PaymentType;

        if ($data['method'] === MerchantType::CC->value) {
            $creditCard = new CreditCardType;
            $creditCard->setCardNumber($data['card_number'])->setExpirationDate($data['expiry']);
            $creditCard->setCardCode($data['cvv']);
            $paymentType->setCreditCard($creditCard);
        }

        if ($data['method'] === MerchantType::ACH->value) {
            $bankAccount = new BankAccountType;
            $bankAccount->setAccountNumber($data['account_number'])
                ->setRoutingNumber($data['routing_number'])
                ->setNameOnAccount($data['first_name'] . ' ' . $data['last_name'])
                ->setBankName('Wells Fargo Bank NA');
            $paymentType->setBankAccount($bankAccount);
        }

        $billToAndShippingAddress = new CustomerAddressType;
        $billToAndShippingAddress->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setCompany($consumer->company->name)
            ->setAddress($data['address'])
            ->setCity($data['city'])
            ->setState($data['state'])
            ->setZip($data['zip'])
            ->setCountry('USA');

        $billToAndShippingAddress->setPhoneNumber($consumer->consumerProfile?->mobile)
            ->setFaxNumber('999-999-9999');

        $paymentProfile = new CustomerPaymentProfileType;
        $paymentProfile->setCustomerType('individual')->setBillTo($billToAndShippingAddress);
        $paymentProfile->setPayment($paymentType);

        $customerProfile = new CustomerProfileType;
        $customerProfile->setDescription($data['first_name'] . ' ' . $data['last_name'])
            ->setMerchantCustomerId(sprintf('%s_%s', $consumer->id, time()))
            ->setEmail($consumer->consumerProfile?->email);

        $customerProfile->setPaymentProfiles([$paymentProfile])->setShipToList([$billToAndShippingAddress]);

        $customerProfileRequest = new CreateCustomerProfileRequest;
        $customerProfileRequest->setMerchantAuthentication($merchantAuthentication);
        $customerProfileRequest->setValidationMode('testMode')->setRefId('ref-' . time());
        $customerProfileRequest->setProfile($customerProfile);

        $controller = new CreateCustomerProfileController($customerProfileRequest);

        return $controller->executeWithApiResponse(config('services.authorize_environment'));
    }

    /**
     * @param array{
     *  first_name: string,
     *  last_name: string,
     *  address: string,
     *  city: string,
     *  state: string,
     *  zip: string,
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     *  amount: float,
     *  method: string,
     *  is_pif: bool,
     * } $data
     */
    public function makePayment(ExternalPaymentProfile $externalPaymentProfile, array $data, Merchant $merchant): mixed
    {
        $response = $this->createPaymentProfile($data, $externalPaymentProfile->consumer, $merchant);

        if ($response->getMessages()->getResultCode() === 'Ok') {
            $profileId = $response->getCustomerProfileId();
            $paymentProfileId = $response->getCustomerPaymentProfileIdList()[0];

            $externalPaymentProfile->update([
                'profile_id' => $profileId,
                'payment_profile_id' => $paymentProfileId,
            ]);

            $response = $this->proceedPayment(
                $merchant->authorize_login_id,
                $merchant->authorize_transaction_key,
                (float) $data['amount'],
                $profileId,
                $paymentProfileId,
            );

            $transactionResponse = $response->getTransactionResponse();
            if ($response->getMessages()->getResultCode() === 'Ok' && $transactionResponse !== null && $transactionResponse->getMessages() !== null) {
                $shares = $data['is_pif']
                    ? app(CompanyMembershipService::class)->fetchShares($externalPaymentProfile->consumer, (float) $data['amount'])
                    : app(ScheduleTransactionService::class)->calculateShareAmount($externalPaymentProfile->consumer, (float) $data['amount']);

                $transaction = Transaction::query()->create([
                    'company_id' => $externalPaymentProfile->company_id,
                    'subclient_id' => $externalPaymentProfile->subclient_id,
                    'consumer_id' => $externalPaymentProfile->consumer_id,
                    'external_payment_profile_id' => $externalPaymentProfile->id,
                    'transaction_id' => $transactionResponse->getTransId(),
                    'status' => TransactionStatus::SUCCESSFUL,
                    'transaction_type' => $data['is_pif'] ? TransactionType::PIF : TransactionType::PARTIAL_PIF,
                    'payment_mode' => $data['method'],
                    'amount' => number_format((float) $data['amount'], 2, thousands_separator: ''),
                    'status_code' => $transactionResponse->getResponseCode(),
                    'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                    'superadmin_process' => 0,
                    'rnn_share' => $shares['yn_share'],
                    'company_share' => $shares['company_share'],
                    'revenue_share_percentage' => $shares['share_percentage'],
                    'gateway_response' => $transactionResponse,
                ]);

                return $transaction->id;
            }

            if ($transactionResponse->getErrors() !== null) {
                Log::channel('daily')->error('Failed authorize merchant payment for donation', [
                    'data' => $data,
                    'status code' => $transactionResponse->getErrors()[0]->getErrorCode(),
                    'Stack Trace' => $transactionResponse->getErrors(),
                ]);

                throw new Exception('Failed authorize merchant payment for donation');
            }
        }

        throw new Exception('Invalid payment details, please try again.');
    }
}
