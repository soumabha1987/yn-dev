<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MerchantType;
use App\Exceptions\MerchantPaymentException;
use Exception;
use Illuminate\Support\Facades\Log;
use SoapClient;

class UsaepayPaymentService
{
    /**
     * @throws MerchantPaymentException
     */
    public function proceedPayment(...$data)
    {
        $soapClient = app(SoapClient::class, ['wsdl' => config('services.usaepay_url')]);

        $token = $this->getToken($data['transaction_key'], $data['transaction_pin']);

        $parameters = [
            'Command' => $data['payment_method'] === MerchantType::CC ? 'Sale' : 'Check',
            'Details' => [
                'Invoice' => rand(1232132, 9999999),
                'PONum' => null,
                'OrderID' => null,
                'Description' => 'Younegotiate consumer payment for installment',
                'Amount' => $data['amount'],
            ],
        ];

        try {
            $paymentMethods = $soapClient->getCustomerPaymentMethods($token, $data['profile_id']);

            return $soapClient->runCustomerTransaction($token, $data['profile_id'], $paymentMethods[0]->MethodID, $parameters);
        } catch (Exception $exception) {
            Log::channel('daily')->error('Something went wrong in usa epay service', [
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            throw new MerchantPaymentException('Oops! USAepay payment service has something went wrong');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getToken(string $sourceKey, string $pin): array
    {
        $seed = time() . rand();

        $clear = $sourceKey . $seed . $pin;

        $hash = sha1($clear);

        return [
            'SourceKey' => $sourceKey,
            'PinHash' => [
                'Type' => 'sha1',
                'Seed' => $seed,
                'HashValue' => $hash,
            ],
            'ClientIP' => gethostbyname(gethostname()),
        ];
    }
}
