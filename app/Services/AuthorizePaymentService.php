<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MerchantPaymentException;
use Exception;
use Illuminate\Support\Facades\Log;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\CustomerProfilePaymentType;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\PaymentProfileType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\controller\CreateTransactionController;

class AuthorizePaymentService
{
    /**
     * @param  string|array|float  ...$data
     *
     * @throws MerchantPaymentException
     */
    public function proceedPayment(...$data)
    {
        $authorizeMerchant = new MerchantAuthenticationType;

        $authorizeMerchant->setName($data['authorize_login_id']);
        $authorizeMerchant->setTransactionKey($data['authorize_transaction_key']);

        $order = new OrderType;
        $order->setInvoiceNumber((string) mt_rand(100000000, 999999999));
        $order->setDescription('Younegotiate consumer payment of installment');

        $transactionRequestType = new TransactionRequestType;
        $transactionRequestType->setTransactionType('authCaptureTransaction');
        $transactionRequestType->setAmount($data['amount']);
        $transactionRequestType->setOrder($order);

        $profileToCharge = new CustomerProfilePaymentType;
        $profileToCharge->setCustomerProfileId($data['consumer_profile_id']);
        $paymentProfileType = new PaymentProfileType;
        $paymentProfileType->setPaymentProfileId($data['payment_profile_id']);
        $profileToCharge->setPaymentProfile($paymentProfileType);

        $transactionRequestType->setProfile($profileToCharge);

        $request = new CreateTransactionRequest;
        $request->setMerchantAuthentication($authorizeMerchant);
        $request->setRefId('ref' . now()->timestamp);
        $request->setTransactionRequest($transactionRequestType);

        try {
            $controller = new CreateTransactionController($request);

            return $controller->executeWithApiResponse(config('services.authorize_environment'));

        } catch (Exception $exception) {
            Log::channel('daily')->error('Something went wrong in authorize payment service', [
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            throw new MerchantPaymentException('Oops! Authorize payment service has something went wrong');
        }
    }
}
