<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ExternalPaymentProfile;
use App\Models\Merchant;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\StripePaymentDetail;
use App\Models\Transaction;
use App\Services\ScheduleTransactionService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePaymentService extends SchedulePlanPaymentService
{
    public function payRemainingAmount(
        Merchant $merchant,
        Consumer $consumer,
        ConsumerNegotiation $consumerNegotiation,
        Collection $scheduleTransactions
    ): bool {
        /** @var ScheduleTransaction $scheduleTransaction */
        $scheduleTransaction = $scheduleTransactions->first();

        $stripePaymentDetail = StripePaymentDetail::query()
            ->where('id', $scheduleTransaction->stripe_payment_detail_id)
            ->first();

        if (! $stripePaymentDetail) {
            return false;
        }

        $totalAmounts = $scheduleTransactions->sum('amount');

        try {
            $response = $this->proceedPayment(
                secretKey: $merchant->stripe_secret_key,
                stripePaymentDetail: $stripePaymentDetail,
                consumer: $consumer,
                amount: $totalAmounts
            );

            if ($response->status === PaymentIntent::STATUS_SUCCEEDED) {
                $this->successfulTransactionOfRemainingBalance(
                    transactionId: $response->id,
                    transactionResponse: $response->toArray(),
                    scheduleTransactions: $scheduleTransactions,
                    consumer: $consumer,
                    consumerNegotiation: $consumerNegotiation,
                    statusCode: 'A'
                );

                return true;
            }

            if ($response->status === PaymentIntent::STATUS_CANCELED) {
                $transaction = $this->failedTransaction(
                    transactionId: $response->id,
                    consumer: $consumer,
                    transactionResponse: $response->toArray(),
                    transactionType: TransactionType::PARTIAL_PIF
                );

                $transaction->status_code = null;
                $transaction->amount = $totalAmounts;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay remaining balance of stripe', [
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
        ConsumerNegotiation $consumerNegotiation
    ): bool {
        $stripePaymentDetail = StripePaymentDetail::query()
            ->where('id', $scheduleTransaction->stripe_payment_detail_id)
            ->first();

        if (! $stripePaymentDetail) {
            return false;
        }

        $amount = (float) $scheduleTransaction->amount;

        try {
            $response = $this->proceedPayment(
                secretKey: $merchant->stripe_secret_key,
                stripePaymentDetail: $stripePaymentDetail,
                consumer: $consumer,
                amount: $amount
            );

            if ($response->status === PaymentIntent::STATUS_SUCCEEDED) {
                $this->successfulTransactionOfInstallment(
                    transactionId: $response->id,
                    transactionResponse: $response->toArray(),
                    consumer: $consumer,
                    consumerNegotiation: $consumerNegotiation,
                    scheduleTransaction: $scheduleTransaction,
                    statusCode: 'A',
                );

                return true;
            }

            if ($response->status === PaymentIntent::STATUS_CANCELED) {
                $transaction = $this->failedTransaction(
                    transactionId: $response->id,
                    consumer: $consumer,
                    transactionResponse: $response->toArray(),
                    transactionType: TransactionType::INSTALLMENT
                );

                $transaction->status_code = null;
                $transaction->amount = $amount;
                $transaction->save();

                return false;
            }

            return false;
        } catch (Exception $exception) {
            Log::channel('daily')->error('There are error in pay installment of stripe payment', [
                'consumer' => $consumer->id,
                'payment_profile' => $consumer->paymentProfile->id,
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            return false;
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
     *  card_number: string,
     *  expiry: string,
     *  cvv: string,
     * } $data
     */
    public function createOrUpdateCustomerProfile(array $data, Merchant $merchant, PaymentProfile $paymentProfile): void
    {
        [$month, $year] = explode('/', $data['expiry']);

        $stripe = new StripeClient($merchant->stripe_secret_key);

        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => $data['card_number'],
                'exp_month' => $month,
                'exp_year' => $year,
                'cvc' => $data['cvv'],
            ],
        ]);

        $customerId = null;

        if (filled($paymentProfile->stripe_customer_id) && filled($paymentMethod->id)) {
            $stripeCustomer = $stripe->customers->retrieve($paymentProfile->stripe_customer_id);

            if ($stripeCustomer->id) {
                $customerId = $stripeCustomer->id;
                if ($stripeCustomer->id === $paymentProfile->stripe_customer_id && ! $stripeCustomer->isDeleted()) {
                    $stripe->paymentMethods->attach($paymentMethod->id, ['customer' => $stripeCustomer->id]);
                }
            }
        }

        if (blank($paymentProfile->stripe_customer_id) && filled($paymentMethod->id)) {
            $stripeCustomer = $stripe->customers->create([
                'address' => [
                    'line1' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'postal_code' => $data['zip'],
                ],
                'description' => $paymentProfile->consumer->company->company_name . ' Billing Via YouNegotiate',
                'metadata' => [
                    'consumer_id' => $paymentProfile->consumer_id,
                ],
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'payment_method' => $paymentMethod->id,
            ]);

            $customerId = $stripeCustomer->id;
        }

        $paymentProfile->update([
            'last4digit' => Str::substr($data['card_number'], -4),
            'expirity' => $data['expiry'],
            'stripe_payment_method_id' => $paymentMethod->id,
            'stripe_customer_id' => $customerId,
        ]);

        StripePaymentDetail::query()->where('consumer_id', $paymentProfile->consumer_id)->delete();

        StripePaymentDetail::query()->create([
            'payment_profile_id' => $paymentProfile->id,
            'stripe_payment_method_id' => $paymentMethod->id,
            'stripe_customer_id' => $customerId,
            'consumer_id' => $paymentProfile->consumer_id,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function proceedPayment(
        string $secretKey,
        StripePaymentDetail $stripePaymentDetail,
        Consumer $consumer,
        float $amount
    ): mixed {
        $stripe = new StripeClient($secretKey);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount * 100,
            'currency' => 'usd',
            'customer' => $stripePaymentDetail->stripe_customer_id,
            'payment_method' => $stripePaymentDetail->stripe_payment_method_id,
            'description' => 'Younegotiate consumer payment for remaining balance or installment',
            'shipping' => [
                'name' => $consumer->first_name . $consumer->last_name,
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

        return $paymentIntent;
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
     *  method:string,
     *  is_pif: bool,
     * } $data
     */
    public function makePayment(ExternalPaymentProfile $externalPaymentProfile, array $data, Merchant $merchant): mixed
    {
        [$month, $year] = explode('/', $data['expiry']);

        $stripe = new StripeClient($merchant->stripe_secret_key);

        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => $data['card_number'],
                'exp_month' => $month,
                'exp_year' => $year,
                'cvc' => $data['cvv'],
            ],
        ]);

        $stripeCustomer = $stripe->customers->create([
            'address' => [
                'line1' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'postal_code' => $data['zip'],
            ],
            'description' => 'Donate to ' . $externalPaymentProfile->consumer->first_name . ' ' . $externalPaymentProfile->consumer->last_name,
            'metadata' => [
                'consumer_id' => $externalPaymentProfile->consumer_id,
            ],
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'payment_method' => $paymentMethod->id,
        ]);

        $externalPaymentProfile->update([
            'stripe_payment_method_id' => $paymentMethod->id,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) ($data['amount'] * 100),
            'currency' => 'usd',
            'customer' => $stripeCustomer->id,
            'payment_method' => $paymentMethod->id,
            'description' => 'Younegotiate consumer payment for remaining balance or installment',
            'shipping' => [
                'name' => $externalPaymentProfile->consumer->first_name . ' ' . $externalPaymentProfile->consumer->last_name,
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

        if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {

            $shares = $data['is_pif']
                ? app(CompanyMembershipService::class)->fetchShares($externalPaymentProfile->consumer, (float) $data['amount'])
                : app(ScheduleTransactionService::class)->calculateShareAmount($externalPaymentProfile->consumer, (float) $data['amount']);

            $transaction = Transaction::query()->create([
                'company_id' => $externalPaymentProfile->company_id,
                'subclient_id' => $externalPaymentProfile->subclient_id,
                'consumer_id' => $externalPaymentProfile->consumer_id,
                'external_payment_profile_id' => $externalPaymentProfile->id,
                'transaction_id' => $paymentIntent->id,
                'transaction_type' => $data['is_pif'] ? TransactionType::PIF : TransactionType::PARTIAL_PIF,
                'status' => TransactionStatus::SUCCESSFUL,
                'gateway_response' => $paymentIntent->toArray(),
                'payment_mode' => $data['method'],
                'amount' => number_format((float) $data['amount'], 2, thousands_separator: ''),
                'status_code' => 'A',
                'rnn_invoice_id' => (Transaction::max('rnn_invoice_id') ?? 9000) + 1,
                'superadmin_process' => 0,
                'rnn_share' => $shares['yn_share'],
                'company_share' => $shares['company_share'],
                'revenue_share_percentage' => $shares['share_percentage'],
            ]);

            return $transaction->id;
        }

        if ($paymentIntent->status === PaymentIntent::STATUS_CANCELED) {
            Log::channel('daily')->error('External payment failed of stripe merchant', [
                'data' => $data,
                'consumer_id' => $externalPaymentProfile->consumer_id,
                'merchant' => $merchant,
            ]);

            throw new Exception('Invalid payment details, please try again.');
        }

        Log::channel('daily')->error('External payment failed of stripe merchant', [
            'data' => $data,
            'consumer_id' => $externalPaymentProfile->consumer_id,
            'merchant' => $merchant,
        ]);

        throw new Exception('Invalid payment details, please try again.');
    }
}
