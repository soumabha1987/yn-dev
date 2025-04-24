<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CompanyMembershipStatus;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipTransactionStatus;
use App\Mail\ExpiredPlanNotificationMail;
use App\Models\CompanyMembership;
use App\Models\MembershipTransaction;
use App\Services\PartnerService;
use App\Services\TilledPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class MembershipPlanAutoRenewPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CompanyMembership $companyMembership,
    ) {
        $companyMembership->loadMissing(['company.membershipPaymentProfile', 'membership']);
    }

    public function handle(TilledPaymentService $tilledPaymentService): void
    {
        // This calculation is for required to send an amount to tilled js.
        // @see https://docs.tilled.com/api/#tag/PaymentIntents/operation/CreatePaymentIntent
        $amount = intval(((float) $this->companyMembership->membership->price) * 100);

        $response = $this->createPayment($tilledPaymentService, $amount);

        if ($this->isPaymentSuccessful($amount, $response)) {
            $this->updateCompanyMembership();

            $this->createMembershipTransaction($amount, $response, MembershipTransactionStatus::SUCCESS->value);

            return;
        }

        $this->createMembershipTransaction($amount, $response, MembershipTransactionStatus::FAILED->value);

        // We allow the user to check if the next membership is not available.
        // In that case, we need to renew the current membership.
        $membership = $this->companyMembership->nextMembershipPlan ?? $this->companyMembership->membership;

        $this->companyMembership->update([
            'membership_id' => $membership->id,
            'status' => CompanyMembershipStatus::INACTIVE,
            'next_membership_plan_id' => null,
        ]);

        $content = $this->companyMembership->current_plan_end->gte(now()->subDay())
            ? __('Your membership transaction has failed. We will attempt to reprocess it in 24 hours.')
            : __('Your membership transaction has failed. Please update your payment details.');

        Mail::to($this->companyMembership->company->owner_email)->send(new ExpiredPlanNotificationMail($content));
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function createMembershipTransaction(int $amount, array $response, string $status): void
    {
        $this->companyMembership->loadMissing('company.partner');

        $partnerRevenueShare = 0;

        if ($this->companyMembership->company->partner_id) {
            $partnerRevenueShare = app(PartnerService::class)
                ->calculatePartnerRevenueShare($this->companyMembership->company->partner, $amount / 100);
        }

        MembershipTransaction::query()
            ->create([
                'status' => $status,
                'company_id' => $this->companyMembership->company_id,
                'membership_id' => $this->companyMembership->membership_id,
                'price' => $amount / 100,
                'tilled_transaction_id' => $response['id'] ?? null,
                'response' => $response,
                'plan_end_date' => match ($this->companyMembership->membership->frequency) {
                    MembershipFrequency::WEEKLY => $this->companyMembership->current_plan_end->addWeek(),
                    MembershipFrequency::MONTHLY => $this->companyMembership->current_plan_end->addMonthNoOverflow(),
                    MembershipFrequency::YEARLY => $this->companyMembership->current_plan_end->addYear(),
                },
                'partner_revenue_share' => $partnerRevenueShare,
            ]);
    }

    protected function createPayment(TilledPaymentService $tilledPaymentService, int $amount): array
    {
        $response = [];

        if ($amount > 0) {
            $response = $tilledPaymentService->createPaymentIntents($amount, $this->companyMembership->company->membershipPaymentProfile?->tilled_payment_method_id);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function isPaymentSuccessful(int $amount, array $response): bool
    {
        // Allow users to choose $0 amount memberships for free, especially during development.
        // However, after going live, confirmation with the client may be required.
        return $amount === 0 || in_array(optional($response)['status'], ['processing', 'succeeded']);
    }

    protected function updateCompanyMembership(): void
    {
        $this->companyMembership->update([
            'current_plan_start' => $this->companyMembership->current_plan_end,
            'current_plan_end' => match ($this->companyMembership->membership->frequency) {
                MembershipFrequency::WEEKLY => $this->companyMembership->current_plan_end->addWeek(),
                MembershipFrequency::MONTHLY => $this->companyMembership->current_plan_end->addMonthNoOverflow(),
                MembershipFrequency::YEARLY => $this->companyMembership->current_plan_end->addYear(),
            },
            'status' => CompanyMembershipStatus::ACTIVE,
        ]);
    }
}
