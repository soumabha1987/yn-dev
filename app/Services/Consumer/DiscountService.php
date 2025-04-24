<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Models\Company;
use App\Models\Consumer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class DiscountService
{
    /**
     * @return array{
     *    discount: mixed,
     *    percentage: mixed,
     *    discountedAmount: mixed,
     *    message: string,
     * }
     */
    public function fetchAmountToPayWhenPif(Consumer $consumer): array
    {
        $currentBalance = (float) $consumer->current_balance;

        return match (true) {
            $consumer->pif_discount_percent !== null => [
                'discount' => (float) number_format($currentBalance - $discountedAmount = ($currentBalance * (float) $consumer->pif_discount_percent / 100), 2, thousands_separator: ''),
                'percentage' => $consumer->pif_discount_percent,
                'discountedAmount' => $discountedAmount,
                'message' => 'pif_discount_percent',
            ],

            default => $this->getPifCompanyTerms($consumer),
        };
    }

    /**
     * @return array{
     *    discount: mixed,
     *    message: string,
     * }
     */
    private function getPifCompanyTerms(Consumer $consumer): array
    {
        $currentBalance = (float) $consumer->current_balance;

        /** @var Company $company */
        $company = $consumer->company;

        return match (true) {
            $consumer->subclient_id !== null && $consumer->subclient->pif_balance_discount_percent !== null => [
                'discount' => (float) number_format($currentBalance - $subclientDiscountedAmount = ($currentBalance * (float) $consumer->subclient?->pif_balance_discount_percent / 100), 2, thousands_separator: ''),
                'percentage' => $consumer->subclient?->pif_balance_discount_percent,
                'discountedAmount' => $subclientDiscountedAmount,
                'message' => 'subclient discount percentage',
            ],
            default => [
                'discount' => (float) number_format($currentBalance - $companyDiscountedAmount = ($currentBalance * (float) $company->pif_balance_discount_percent / 100), 2, thousands_separator: ''),
                'percentage' => $company->pif_balance_discount_percent,
                'discountedAmount' => $companyDiscountedAmount,
                'message' => 'company discount percentage',
            ],
        };
    }

    public function fetchAmountToPayWhenPpa(Consumer $consumer): int|float
    {
        $currentBalance = (float) $consumer->current_balance;

        return match (true) {
            $consumer->pay_setup_discount_percent !== null => $currentBalance - ($currentBalance * (float) $consumer->pay_setup_discount_percent / 100),
            default => $this->getPpaCompanyTerms($consumer),
        };
    }

    private function getPpaCompanyTerms(Consumer $consumer): float
    {
        $currentBalance = (float) $consumer->current_balance;

        return match (true) {
            $consumer->subclient_id !== null && $consumer->subclient !== null && $consumer->subclient->ppa_balance_discount_percent !== null => $currentBalance - ((float) $currentBalance * (float) $consumer->subclient->ppa_balance_discount_percent / 100),
            default => $currentBalance - ((float) $currentBalance * (float) $consumer->company->ppa_balance_discount_percent / 100),
        };
    }

    /**
     * @return array{ $minSettlementPercentage: int|null, $maxFirstPayDays: int|null }
     */
    public function getPifMinimumPercentageAndMaxDate(Consumer $consumer): array
    {
        return match (true) {
            $consumer->pay_setup_discount_percent !== null => [$consumer->minimum_settlement_percentage, $consumer->max_first_pay_days],
            default => $this->getPifCompanyMinimumPercentageAndMaxDate($consumer),
        };
    }

    /**
     * @return array{ $minSettlementPercentage: int|null, $maxFirstPayDays: int|null}
     */
    private function getPifCompanyMinimumPercentageAndMaxDate(Consumer $consumer): array
    {
        return match (true) {
            $consumer->subclient_id !== null && $consumer->subclient !== null && $consumer->subclient->ppa_balance_discount_percent !== null => [$consumer->subclient->minimum_settlement_percentage, $consumer->subclient->max_first_pay_days],
            default => [$consumer->company->minimum_settlement_percentage, $consumer->company->max_first_pay_days],
        };
    }

    /**
     * @return array{ $minPaymentPlanPercentage: int|null, $maxFirstPayDays: int|null}
     */
    public function getPpaMinimumPercentageAndMaxDate(Consumer $consumer): array
    {
        return match (true) {
            $consumer->pay_setup_discount_percent !== null => [$consumer->minimum_payment_plan_percentage, $consumer->max_first_pay_days],
            default => $this->getCompanyPpaMinimumPercentageAndMaxDate($consumer),
        };
    }

    /**
     * @return array{ $minPaymentPlanPercentage: int|null, $maxFirstPayDays: int|null}
     */
    private function getCompanyPpaMinimumPercentageAndMaxDate(Consumer $consumer): array
    {
        return match (true) {
            $consumer->subclient_id !== null && $consumer->subclient !== null && $consumer->subclient->ppa_balance_discount_percent !== null => [$consumer->subclient->minimum_payment_plan_percentage, $consumer->subclient->max_first_pay_days],
            default => [$consumer->company->minimum_payment_plan_percentage, $consumer->company->max_first_pay_days],
        };
    }

    public function fetchMonthlyAmount(Consumer $consumer, mixed $ppaDiscountAmount = null): int|float
    {
        $ppaDiscountAmount = (float) $ppaDiscountAmount;

        return match (true) {
            $ppaDiscountAmount !== null && ($minMonthlyPayPercent = $consumer->min_monthly_pay_percent) !== null => ($ppaDiscountAmount * $minMonthlyPayPercent) / 100,
            default => $this->getMonthlyCompanyTerms($consumer, $ppaDiscountAmount),
        };
    }

    private function getMonthlyCompanyTerms(Consumer $consumer, mixed $ppaDiscountAmount = null): float
    {
        $minimumMonthlyPayPercentage = match (true) {
            $consumer->subclient_id !== null && $consumer->subclient !== null && $consumer->subclient->min_monthly_pay_percent !== null => $consumer->subclient->min_monthly_pay_percent,
            default => $consumer->company->min_monthly_pay_percent,
        };

        if ($ppaDiscountAmount) {
            return ((float) $ppaDiscountAmount * (float) $minimumMonthlyPayPercentage) / 100;
        }

        return ((float) $consumer->current_balance * (float) $minimumMonthlyPayPercentage) / 100;
    }

    /**
     * @return array{
     *  first_pay_date: mixed,
     *  max_first_pay_days: Carbon
     * }
     */
    public function fetchMaxDateForFirstPayment(Consumer $consumer): array
    {
        /** @var Company $company */
        $company = $consumer->company;

        $noOfDays = match (true) {
            ($consumerMaximumDaysOfFirstPay = $consumer->max_days_first_pay) !== null && $consumerMaximumDaysOfFirstPay !== '' => $consumerMaximumDaysOfFirstPay,
            $consumer->subclient_id !== null && $consumer->subclient !== null && $consumer->subclient->max_days_first_pay !== null => $consumer->subclient->max_days_first_pay,
            $company->max_days_first_pay !== null => $company->max_days_first_pay,
            default => 30,
        };

        return [
            'no_of_days' => $noOfDays,
            'max_first_pay_date' => today()->addDays((int) $noOfDays),
        ];
    }

    public function calculateInstallments(float $minimumPpaDiscountedAmount, float $amount): array
    {
        $installments = floor($minimumPpaDiscountedAmount / $amount);

        if (($installments * $amount) > $minimumPpaDiscountedAmount) {
            $installments--;
        }

        $lastInstallmentAmount = number_format($minimumPpaDiscountedAmount - ($amount * $installments), 2, thousands_separator: '');

        if ($lastInstallmentAmount < 10 && $lastInstallmentAmount > 0) {
            $installments--;
            $lastInstallmentAmount = number_format((float) $lastInstallmentAmount + $amount, 2, thousands_separator: '');
        }

        return [$installments, $lastInstallmentAmount];
    }

    /**
     * @return array{
     *   message: string,
     *   installments: float,
     *   monthly_amount: float,
     *   last_month_amount: float,
     *   discounted_amount: float,
     *   discount_percentage: string
     * }
     */
    public function fetchInstallmentDetails(Consumer $consumer)
    {
        $minimumPpaDiscountedAmount = $this->fetchAmountToPayWhenPpa($consumer);

        $amount = (float) number_format($this->fetchMonthlyAmount($consumer, $minimumPpaDiscountedAmount), 2, thousands_separator: '');

        $numberOfInstallments = floor(round($minimumPpaDiscountedAmount / $amount, 10));
        $lastInstallmentAmount = (float) number_format($minimumPpaDiscountedAmount - ($amount * $numberOfInstallments), 2, thousands_separator: '');

        if ($lastInstallmentAmount < 10 && $lastInstallmentAmount > 0) {
            $numberOfInstallments--;
            $lastInstallmentAmount = $amount + $lastInstallmentAmount;
        }

        $message = __(':installments monthly payments of :monthlyAmount', [
            'installments' => $numberOfInstallments,
            'monthlyAmount' => Number::currency($amount),
        ]);

        if ($lastInstallmentAmount > 0) {
            $message = __(':installments monthly payments of :monthlyAmount and last payment of :lastMonthAmount', [
                'installments' => $numberOfInstallments,
                'monthlyAmount' => Number::currency($amount),
                'lastMonthAmount' => Number::currency($lastInstallmentAmount),
            ]);
        }

        return [
            'message' => $message,
            'installments' => $numberOfInstallments,
            'monthly_amount' => $amount,
            'last_month_amount' => $lastInstallmentAmount,
            'discounted_amount' => (float) number_format($consumer->current_balance - $minimumPpaDiscountedAmount, 2, thousands_separator: ''),
            'discount_percentage' => Number::percentage(100 - ($minimumPpaDiscountedAmount / (float) $consumer->current_balance) * 100),
        ];
    }
}
