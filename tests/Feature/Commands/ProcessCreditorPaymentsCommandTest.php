<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\ProcessCreditorPaymentsCommand;
use App\Enums\TransactionStatus;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use App\Models\Transaction;
use App\Models\YnTransaction;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessCreditorPaymentsCommandTest extends TestCase
{
    #[Test]
    public function it_can_not_process_creditor_payments_because_there_is_no_transaction(): void
    {
        Company::factory()->create();

        $this->artisan(ProcessCreditorPaymentsCommand::class)->assertOk();

        $this->assertDatabaseCount(YnTransaction::class, 0);
    }

    #[Test]
    public function it_can_process_creditor_payment(): void
    {
        $this->travelTo(now()->addMinutes(10)->addSeconds(10));

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        Http::fake(fn () => Http::response(['status' => 'succeeded']));

        $transaction = Transaction::factory()
            ->for($company = Company::factory()->create(['tilled_merchant_account_id' => null]))
            ->create([
                'rnn_share_pass' => null,
                'yn_transaction_id' => null,
                'status' => TransactionStatus::SUCCESSFUL,
                'superadmin_process' => false,
                'rnn_share' => $rnnShare = fake()->randomFloat(2, 1),
            ]);

        MembershipPaymentProfile::factory()->create(['company_id' => $company->id]);

        $transaction->forceFill(['created_at' => now()->subDays(3)->toDateTimeString()]);

        $transaction->save();

        $this->artisan(ProcessCreditorPaymentsCommand::class)->assertOk();

        $from = now()->subWeek()->toDateTimeString();
        $to = now()->subDay()->toDateTimeString();

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $company->id,
            'amount' => number_format((float) $rnnShare, 2, thousands_separator: ''),
            'response->status' => 'succeeded',
            'billing_cycle_start' => $from,
            'billing_cycle_end' => $to,
            'email_count' => 0,
            'sms_count' => 0,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'rnn_invoice_id' => 5000,
        ]);

        $transaction->refresh();

        $this->assertNotNull($transaction->rnn_share_pass);
        $this->assertNotNull($transaction->yn_transaction_id);
    }

    #[Test]
    public function it_can_process_creditor_payment_with_tilled_merchant(): void
    {
        $this->travelTo(now()->addMinutes(10)->addSeconds(10));

        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);

        $status = fake()->randomElement(['succeeded', 'processing']);

        Http::fake(fn () => Http::response(['status' => $status]));

        [$transaction, $tilledTransaction] = Transaction::factory()
            ->forEachSequence(
                [
                    'rnn_share' => $rnnShare = fake()->randomFloat(2, 1),
                    'company_id' => $companyId = Company::factory()->create(['tilled_merchant_account_id' => null])->id,
                ],
                [
                    'rnn_share' => $tilledRnnShare = fake()->randomFloat(2, 1),
                    'company_id' => $tilledCompanyId = Company::factory()->create()->id,
                ]
            )
            ->create([
                'rnn_share_pass' => null,
                'yn_transaction_id' => null,
                'status' => TransactionStatus::SUCCESSFUL,
                'superadmin_process' => false,
                'created_at' => now()->subDays(3)->toDateTimeString(),
            ]);

        MembershipPaymentProfile::factory()->create(['company_id' => $companyId]);

        $this->artisan(ProcessCreditorPaymentsCommand::class)->assertOk();

        $from = now()->subWeek()->toDateTimeString();
        $to = now()->subDay()->toDateTimeString();

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $companyId,
            'amount' => number_format($rnnShare, 2, thousands_separator: ''),
            'response->status' => $status,
            'billing_cycle_start' => $from,
            'billing_cycle_end' => $to,
            'email_count' => 0,
            'sms_count' => 0,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'rnn_invoice_id' => 5000,
        ]);

        $this->assertDatabaseMissing(YnTransaction::class, [
            'company_id' => $tilledCompanyId,
            'amount' => number_format($tilledRnnShare, 2, thousands_separator: ''),
            'response->status' => $status,
            'billing_cycle_start' => $from,
            'billing_cycle_end' => $to,
            'email_count' => 0,
            'sms_count' => 0,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
        ]);

        $transaction->refresh();

        $this->assertNotNull($transaction->rnn_share_pass);
        $this->assertNotNull($transaction->yn_transaction_id);

        $tilledTransaction->refresh();

        $this->assertNull($tilledTransaction->rnn_share_pass);
        $this->assertNull($tilledTransaction->yn_transaction_id);
    }
}
