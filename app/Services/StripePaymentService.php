<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MerchantPaymentException;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripePaymentService
{
    /**
     * @throws MerchantPaymentException
     */
    public function proceedPayment(...$data)
    {
        try {
            $stripe = new StripeClient($data['secret_key']);

            return $stripe->paymentIntents->create([
                'amount' => $data['amount'] * 100,
                'currency' => 'usd',
                'customer' => $data['stripe_payment_detail']->stripe_customer_id,
                'payment_method' => $data['stripe_payment_detail']->stripe_payment_method_id,
                'description' => 'Younegotiate consumer installment payment',
                'shipping' => [
                    'name' => $data['consumer']->first_name . $data['consumer']->last_name,
                    'address' => [
                        'line1' => '510 Townsend St',
                        'postal_code' => '98140',
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'country' => 'US',
                    ],
                ],
                'off_session' => true,
                'confirm' => true,
            ]);
        } catch (Exception $exception) {
            Log::channel('daily')->error('Something went wrong in stripe payment service', [
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            throw new MerchantPaymentException('Oops! Stripe payment service has something went wrong');
        }
    }
}
