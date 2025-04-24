<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Enums\IndustryType;
use App\Enums\MerchantName;
use App\Jobs\MerchantWebhookCreationJob;
use App\Models\Company;
use App\Models\Merchant;
use App\Models\Subclient;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use net\authorize\api\contract\v1\AuthenticateTestRequest;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use net\authorize\api\controller\AuthenticateTestController;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use SoapFault;
use Stripe\StripeClient;

class MerchantService
{
    public function getByCompany(int $companyId): ?Merchant
    {
        return Merchant::query()
            ->select('id', 'company_id', 'merchant_type', 'merchant_name', 'stripe_secret_key')
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->whereNotNull('verified_at')
            ->first();
    }

    /**
     * @throws ModelNotFoundException<Merchant>
     */
    public function getBySubclient(int $companyId, int $subclientId): Merchant
    {
        return Merchant::query()
            ->select('id', 'company_id', 'merchant_type', 'merchant_name', 'stripe_secret_key')
            ->withExists('paymentProfiles')
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->whereNotNull('verified_at')
            ->firstOrFail();
    }

    /**
     * @return Collection<Merchant>
     */
    public function fetchByNameAndCompany(?string $merchantName, int $companyId): Collection
    {
        return Merchant::query()
            ->select('id', 'merchant_type', 'authorize_login_id', 'authorize_transaction_key', 'usaepay_key', 'usaepay_pin')
            ->withExists('paymentProfiles')
            ->when($merchantName, fn (Builder $query) => $query->where('merchant_name', $merchantName))
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->whereNotNull('verified_at')
            ->get();
    }

    public function fetchByNameAndSubclient(?string $merchantName, int $companyId, int $subclientId): Collection
    {
        return Merchant::query()
            ->select('id', 'merchant_type', 'authorize_login_id', 'authorize_transaction_key', 'usaepay_key', 'usaepay_pin')
            ->withExists('paymentProfiles')
            ->when($merchantName, fn (Builder $query) => $query->where('merchant_name', $merchantName))
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->whereNotNull('verified_at')
            ->get();
    }

    public function deleteByCompany(int $companyId): void
    {
        Merchant::query()->where('company_id', $companyId)->delete();
    }

    public function deleteBySubclient(int $subclientId, int $companyId): void
    {
        Merchant::query()
            ->where('company_id', $companyId)
            ->where('subclient_id', $subclientId)
            ->delete();
    }

    /**
     * @return Collection<Merchant>
     */
    public function getVerifiedOfCompany(int $companyId): Collection
    {
        return Merchant::query()
            ->where('company_id', $companyId)
            ->whereNull('subclient_id')
            ->whereNotNull('verified_at')
            ->get();
    }

    public function verify(Company $company, array $data): bool
    {
        return match ($data['merchant_name']) {
            MerchantName::YOU_NEGOTIATE->value => $this->verifyTilledMerchant($company, $data),
            MerchantName::AUTHORIZE->value => $this->verifyAuthorizedMerchant($data),
            MerchantName::USA_EPAY->value => $this->verifyUSAEpayMerchant($data),
            MerchantName::STRIPE->value => $this->verifyStripeMerchant($data),
            default => false,
        };
    }

    public function verifyForSubclient(Subclient $subclient, array $data): bool
    {
        return match ($data['merchant_name']) {
            MerchantName::YOU_NEGOTIATE->value => $this->verifySubclientTilledMerchant($subclient, $data),
            MerchantName::AUTHORIZE->value => $this->verifyAuthorizedMerchant($data),
            MerchantName::USA_EPAY->value => $this->verifyUSAEpayMerchant($data),
            MerchantName::STRIPE->value => $this->verifyStripeMerchant($data),
            default => false,
        };
    }

    public function verifySubclientTilledMerchant(Subclient $subclient, array $validatedData): bool
    {
        if ($subclient->tilled_merchant_account_id === null) {
            $data = [
                'bank_account' => [
                    'account_holder_name' => $validatedData['account_holder_name'],
                    'account_number' => $validatedData['bank_account_number'],
                    'bank_name' => $validatedData['bank_name'],
                    'currency' => 'usd',
                    'routing_number' => $validatedData['bank_routing_number'],
                    'type' => $validatedData['bank_account_type'],
                ],
                'email' => $validatedData['owner_email'],
                'name' => $subclient->subclient_name,
                'payment_method_type' => 'card',
                'pricing_template_ids' => [
                    config('services.merchant.tilled_ach_pricing_template_id'),
                    config('services.merchant.tilled_cc_pricing_template_id'),
                ],
            ];

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->post('accounts/connected', $data);

            if ($response->failed()) {
                Log::channel('daily')->error('Connected merchant account failed', [
                    'request data' => $data,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                return false;
            }

            $subclient->update(['tilled_merchant_account_id' => $response->json('id')]);

            MerchantWebhookCreationJob::dispatch($response->json('id'));
        }

        if ($subclient->tilled_profile_completed_at === null) {
            $data = [
                'accept_terms_and_conditions' => true,
                'business_legal_entity' => [
                    'address' => [
                        'city' => $subclient->city,
                        'state' => $subclient->state,
                        'country' => 'US',
                        'street' => $subclient->address,
                        'zip' => $subclient->zip,
                    ],
                    'average_transaction_amount' => ((int) $validatedData['average_transaction_amount']) * 100,
                    'category' => $subclient->company_category,
                    'currency' => 'usd',
                    'legal_name' => $validatedData['legal_name'],
                    'locale' => 'en_US',
                    'name' => $subclient->subclient_name,
                    'phone' => Str::of($validatedData['owner_phone'])->split(3)->implode('-'),
                    'principals' => [
                        [
                            'address' => [
                                'city' => $validatedData['owner_city'],
                                'state' => $validatedData['owner_state'],
                                'country' => 'US',
                                'street' => $validatedData['owner_address'],
                                'zip' => $validatedData['owner_zip'],
                            ],
                            'date_of_birth' => $validatedData['dob'],
                            'first_name' => $validatedData['first_name'],
                            'is_applicant' => true,
                            'job_title' => $validatedData['job_title'],
                            'last_name' => $validatedData['last_name'],
                            'percentage_shareholding' => max((int) $validatedData['percentage_shareholding'], 100),
                            'phone' => $validatedData['owner_phone'],
                            'email' => $validatedData['owner_email'],
                            'is_control_prong' => true,
                            'ssn' => in_array($subclient->industry_type->value, IndustryType::ssnIsNotRequired()) ? null : $validatedData['ssn'],
                        ],
                    ],
                    'region' => 'US',
                    'statement_descriptor' => $validatedData['statement_descriptor'],
                    'tax_identification_number' => $validatedData['fed_tax_id'],
                    'type' => $subclient->industry_type->value,
                    'yearly_volume_range' => $validatedData['yearly_volume_range'],
                    'bank_account' => [
                        'account_number' => $validatedData['bank_account_number'],
                        'routing_number' => $validatedData['bank_routing_number'],
                    ],
                ],
            ];

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->put("/applications/$subclient->tilled_merchant_account_id", $data);

            if ($response->failed()) {
                Log::channel('daily')->error('Update merchant application failed', [
                    'request data' => $data,
                    'response status' => $response->status(),
                    'response body' => $response->json(),
                ]);

                return false;
            }

            $accountId = $subclient->tilled_merchant_account_id;

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->post("/applications/$accountId/submit");

            if ($response->failed()) {
                Log::channel('daily')->error('Merchant application submission failed', [
                    'tilled_merchant_account_id' => $accountId,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                return false;
            }

            $subclient->update(['tilled_profile_completed_at' => now()]);

            return true;
        } else {
            $data = [
                'bank_account' => [
                    'account_holder_name' => $validatedData['account_holder_name'],
                    'account_number' => $validatedData['bank_account_number'],
                    'bank_name' => $validatedData['bank_name'],
                    'currency' => 'usd',
                    'routing_number' => $validatedData['bank_routing_number'],
                    'type' => $validatedData['bank_account_type'],
                ],
            ];

            $response = Http::tilled($subclient->tilled_merchant_account_id)->patch('/accounts', $data);

            if ($response->failed()) {
                Log::channel('daily')->error('Update account failed', [
                    'request data' => $data,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                return false;
            }

            return true;
        }
    }

    protected function verifyTilledMerchant(Company $company, array $data): bool
    {
        $dispatchJobToCreateWebhook = false;

        if ($company->tilled_merchant_account_id === null) {
            $tilledData = [
                'bank_account' => [
                    'account_holder_name' => $data['account_holder_name'],
                    'account_number' => $data['bank_account_number'],
                    'bank_name' => $data['bank_name'],
                    'currency' => 'usd',
                    'routing_number' => $data['bank_routing_number'],
                    'type' => $data['bank_account_type'],
                ],
                'email' => $company->owner_email,
                'name' => $company->company_name,
                'payment_method_type' => 'card',
                'pricing_template_ids' => [
                    config('services.merchant.tilled_ach_pricing_template_id'),
                    config('services.merchant.tilled_cc_pricing_template_id'),
                ],
            ];

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->post('accounts/connected', $tilledData);

            if ($response->failed()) {
                Log::channel('daily')->error('Connected merchant account failed', [
                    'request data' => $data,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                Notification::make('tilled')
                    ->title(
                        Str::replace(
                            ['.', '_'],
                            ' ',
                            data_get($response, 'validation_errors.0', __('Please check something went wrong on bank details'))
                        )
                    )
                    ->danger()
                    ->duration(10000)
                    ->send();

                return false;
            }

            $company->update(['tilled_merchant_account_id' => $response->json('id')]);

            $dispatchJobToCreateWebhook = true;
        }

        if ($company->tilled_profile_completed_at === null) {
            $tilledProfileData = [
                'accept_terms_and_conditions' => true,
                'business_legal_entity' => [
                    'address' => [
                        'city' => $data['contact_city'] ?? $company->city ?? $company->membershipPaymentProfile->city,
                        'state' => $data['contact_state'] ?? $company->state ?? $company->membershipPaymentProfile->state,
                        'country' => 'US',
                        'street' => $data['contact_address'] ?? $company->address ?? $company->membershipPaymentProfile->address,
                        'zip' => $data['contact_zip'] ?? $company->zip ?? $company->membershipPaymentProfile->zip,
                    ],
                    'average_transaction_amount' => ((int) $data['average_transaction_amount']) * 100,
                    'category' => $data['company_category'],
                    'currency' => 'usd',
                    'legal_name' => $data['legal_name'],
                    'locale' => 'en_US',
                    'name' => $company->company_name,
                    'phone' => Str::of($company->owner_phone)->split(3)->implode('-'),
                    'principals' => [
                        [
                            'address' => [
                                'city' => $data['contact_city'],
                                'state' => $data['contact_state'],
                                'country' => 'US',
                                'street' => $data['contact_address'],
                                'zip' => $data['contact_zip'],
                            ],
                            'date_of_birth' => $data['dob'],
                            'first_name' => $data['first_name'],
                            'is_applicant' => true,
                            'job_title' => $data['job_title'],
                            'last_name' => $data['last_name'],
                            'percentage_shareholding' => max((int) $data['percentage_shareholding'], 100),
                            'phone' => $company->owner_phone,
                            'email' => $company->owner_email,
                            'is_control_prong' => true,
                            'ssn' => filled($data['ssn']) ? $data['ssn'] : null,
                        ],
                    ],
                    'region' => 'US',
                    'statement_descriptor' => $data['statement_descriptor'],
                    'tax_identification_number' => $data['fed_tax_id'],
                    'type' => $data['industry_type'],
                    'yearly_volume_range' => $data['yearly_volume_range'],
                    'bank_account' => [
                        'account_number' => $data['bank_account_number'],
                        'routing_number' => $data['bank_routing_number'],
                    ],
                ],
            ];

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->put("/applications/$company->tilled_merchant_account_id", $tilledProfileData);

            if (data_get($response, 'validation_errors.0', false)) {
                Log::channel('daily')->error('Update merchant application failed', [
                    'request data' => $tilledProfileData,
                    'response status' => $response->status(),
                    'response body' => $response->json(),
                ]);

                Notification::make('tilled')
                    ->title(Str::replace(['.', '_'], ' ', data_get($response, 'validation_errors.0')))
                    ->danger()
                    ->duration(10000)
                    ->send();

                return false;
            }

            $accountId = $company->tilled_merchant_account_id;

            $company->update([
                'status' => CompanyStatus::SUBMITTED,
                'tilled_profile_completed_at' => now(),
            ]);

            $response = Http::tilled(config('services.merchant.tilled_account'))
                ->post("/applications/$accountId/submit");

            if ($response->failed()) {
                $company->update([
                    'status' => CompanyStatus::STARTED,
                    'tilled_profile_completed_at' => null,
                ]);

                Log::channel('daily')->error('Merchant application submission failed', [
                    'tilled_merchant_account_id' => $accountId,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                Notification::make('tilled')
                    ->title('Please recheck all details, the merchant application has not been submitted.')
                    ->danger()
                    ->duration(10000)
                    ->send();

                return false;
            }

            MerchantWebhookCreationJob::dispatchIf($dispatchJobToCreateWebhook, $company->tilled_merchant_account_id);

            return true;
        } else {
            $data = [
                'bank_account' => [
                    'account_holder_name' => $data['account_holder_name'],
                    'account_number' => $data['bank_account_number'],
                    'bank_name' => $data['bank_name'],
                    'currency' => 'usd',
                    'routing_number' => $data['bank_routing_number'],
                    'type' => $data['bank_account_type'],
                ],
            ];

            $response = Http::tilled($company->tilled_merchant_account_id)->patch('/accounts', $data);

            if ($response->failed()) {
                Log::channel('daily')->error('Update account failed', [
                    'request data' => $data,
                    'response status' => $response->status(),
                    'response body' => $response->body(),
                ]);

                Notification::make('tilled')
                    ->title(
                        Str::replace(
                            ['.', '_'],
                            ' ',
                            data_get($response, 'validation_errors.0', __('The merchant application has not been updated.'))
                        )
                    )
                    ->danger()
                    ->duration(10000)
                    ->send();

                return false;
            }

            return true;
        }
    }

    protected function verifyAuthorizedMerchant(array $data): bool
    {
        $merchantAuthentication = new MerchantAuthenticationType;
        $merchantAuthentication->setName($data['authorize_login_id']);
        $merchantAuthentication->setTransactionKey($data['authorize_transaction_key']);

        $request = new AuthenticateTestRequest;
        $request->setMerchantAuthentication($merchantAuthentication);

        $controller = new AuthenticateTestController($request);

        $response = $controller->executeWithApiResponse(config('services.authorize_environment'));

        if ($response->getMessages()->getResultCode() === 'Ok') {
            return true;
        }

        Notification::make('authorized')
            ->title(__('User authentication failed due to invalid authentication values.'))
            ->danger()
            ->duration(10000)
            ->send();

        return false;
    }

    protected function verifyUSAEpayMerchant(array $data): bool
    {
        try {
            Soap::usaepay($data['usaepay_key'], $data['usaepay_pin'])->getAccountDetails();

            return true;
        } catch (SoapFault $exception) {
            Log::channel('daily')->error('Usaepay verification failed', [
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            Notification::make('usaepay')
                ->title($exception->getMessage())
                ->danger()
                ->duration(10000)
                ->send();

            return false;
        }
    }

    protected function verifyStripeMerchant(array $data): bool
    {
        try {
            $stripe = new StripeClient($data['stripe_secret_key']);

            $stripe->customers->all(['limit' => 1]);

            return true;
        } catch (Exception $exception) {
            Log::channel('daily')->error('Stripe verification failed', [
                'message' => $exception->getMessage(),
                'stack trace' => $exception->getTrace(),
            ]);

            Notification::make('stripe')
                ->title($exception->getMessage())
                ->danger()
                ->duration(10000)
                ->send();

            return false;
        }
    }
}
