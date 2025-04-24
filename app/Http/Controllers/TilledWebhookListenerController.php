<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Mail\SetUpWizardStepsCompletedMail;
use App\Models\Company;
use App\Models\Subclient;
use App\Models\YnTransaction;
use App\Services\CompanyService;
use App\Services\SetupWizardService;
use App\Services\SubclientService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TilledWebhookListenerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = [
            'tilled_signature' => $request->header('tilled-signature'),
            'content' => $request->getContent(),
            'tilled_webhook_secret' => config('services.merchant.tilled_webhook_secret'),
        ];

        $isValidSignature = $this->validateSignature($data);

        if (! $isValidSignature) {
            if (! $this->validateSignatureOfCompanyAndSubclient($request->input('data.account_id'), $data)) {
                return response()->noContent();
            }
        }

        match ($request->get('type')) {
            'account.updated' => $this->accountUpdated($request->all()),
            'payment_intent.failed', 'payment_intent.canceled' => $this->handleFailedConsumerTransaction($request->all()),
            default => Log::channel('daily')->info('Tilled webhook called', $request->all()),
        };

        return response()->noContent();
    }

    /**
     * @param array{
     *  tilled_signature: ?string,
     *  content: string,
     *  tilled_webhook_secret: string,
     * } $requestData
     *
     * @see https://docs.tilled.com/docs/webhooks/signatures/
     */
    private function validateSignature(array $requestData): bool
    {
        if ($requestData['tilled_signature'] === null) {
            return false;
        }

        $signature = explode(',', $requestData['tilled_signature']);

        $data = collect($signature)->reduce(function ($result, $item) {
            [$key, $value] = explode('=', $item);
            $result[$key] = is_numeric($value) ? (int) $value : $value;

            return $result;
        }, []);

        $signedPayload = $data['t'] . '.' . $requestData['content'];

        $actualSignature = hash_hmac('sha256', $signedPayload, $requestData['tilled_webhook_secret']);

        return $actualSignature === $data['v1'];
    }

    private function validateSignatureOfCompanyAndSubclient(?string $tilledMerchantAccountId, array &$data)
    {
        if ($tilledMerchantAccountId === null) {
            return false;
        }

        $company = app(CompanyService::class)->fetchWebhookSecretForValidateSignature($tilledMerchantAccountId);
        if ($company) {
            $data['tilled_webhook_secret'] = $company->tilled_webhook_secret;

            return $this->validateSignature($data);
        }

        $subclient = app(SubclientService::class)->fetchWebhookSecretForValidateSignature($tilledMerchantAccountId);
        if ($subclient) {
            $data['tilled_webhook_secret'] = $subclient->tilled_webhook_secret;

            return $this->validateSignature($data);
        }

        return false;
    }

    /**
     * This event is listened when a merchant account is updated.
     * It is also listened when any of its child accounts, such as company or subclient accounts, are updated.
     *
     * @param  array<string, mixed>  $data
     *
     * @see https://docs.tilled.com/api/#tag/Events/operation/GetEvent
     */
    protected function accountUpdated(array $data): void
    {
        Log::channel('daily')->info('Account updated webhook is called', $data);

        if (data_get($data, 'data.id', false)) {

            /** @var ?Company $company */
            $company = Company::query()->firstWhere('tilled_merchant_account_id', data_get($data, 'data.id'));

            if ($company && filled(data_get($data, 'data.capabilities.0.status'))) {
                $status = data_get($data, 'data.capabilities.0.status');

                $company->loadMissing('creditorUser');

                if (
                    $company->status !== CompanyStatus::ACTIVE
                    && $status === CompanyStatus::ACTIVE->value
                    && app(SetupWizardService::class)->isLastRequiredStepRemaining($company->creditorUser)
                ) {
                    Mail::to($company->owner_email)->send(new SetUpWizardStepsCompletedMail($company));

                    $company->update(['is_wizard_steps_completed' => true]);
                }

                $company->update(['status' => $status]);
            }

            $subclient = Subclient::query()->firstWhere('tilled_merchant_account_id', $data['data']['id']);

            if ($subclient && filled(data_get($data, 'data.capabilities.0.status'))) {
                $subclient->update(['status' => data_get($data, 'data.capabilities.0.status')]);
            }
        }
    }

    private function handleFailedConsumerTransaction(array $data): void
    {
        Log::channel('daily')->info('Failed consumer transaction webhook is called', $data);

        $transaction = app(TransactionService::class)
            ->fetchSuccessfulTilled(data_get($data, 'data.id'), data_get($data, 'data.account_id'));

        if ($transaction) {
            $ynTransactionId = $transaction->yn_transaction_id;

            $transaction->update([
                'status' => TransactionStatus::FAILED,
                'gateway_response' => $data['data'],
                'rnn_share_pass' => null,
                'yn_transaction_id' => null,
            ]);

            YnTransaction::query()->where('id', $ynTransactionId)->delete();

            $transaction->scheduleTransactions()->update([
                'status' => TransactionStatus::FAILED,
            ]);

            if ($transaction->consumer->status === ConsumerStatus::SETTLED) {
                $transaction->consumer()->update([
                    'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                    'current_balance' => $transaction->amount,
                    'has_failed_payment' => true,
                ]);

                $transaction->consumer->consumerNegotiation()->update([
                    'payment_plan_current_balance' => $transaction->amount,
                ]);

                // TODO: Ask to client for sending an email...
            }
        }
    }
}
