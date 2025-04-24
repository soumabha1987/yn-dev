<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\ConsumerStatus;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Enums\SubclientStatus;
use App\Enums\TransactionStatus;
use App\Mail\SetUpWizardStepsCompletedMail;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Models\User;
use App\Models\YnTransaction;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TilledWebhookListenerControllerTest extends TestCase
{
    #[Test]
    public function it_can_update_company_when_tilled_webhook_come(): void
    {
        $company = Company::factory()->create(['tilled_merchant_account_id' => $id = fake()->uuid()]);

        $user = User::factory()->for($company)->create(['subclient_id' => null]);

        $user->assignRole(Role::create(['name' => EnumRole::CREDITOR]));

        config(['services.merchant.tilled_webhook_secret' => 'test']);

        $signature = hash_hmac('sha256', 'test.', 'test');

        $response = $this->withHeaders([
            'tilled-signature' => "v1=$signature,t=test",
        ])->post(route('tilled-webhook-listener'), [
            'type' => 'account.updated',
            'data' => [
                'id' => $id,
                'capabilities' => [
                    [
                        'status' => $status = fake()->randomElement(CompanyStatus::values()),
                    ],
                ],
            ],
        ]);

        $response->assertNoContent();
        $this->assertTrue($company->refresh()->status->value === $status);
    }

    #[Test]
    public function it_can_update_subclient_when_tilled_webhook_come(): void
    {
        $subclient = Subclient::factory()->create(['tilled_merchant_account_id' => $id = fake()->uuid()]);

        config(['services.merchant.tilled_webhook_secret' => 'test']);

        $signature = hash_hmac('sha256', 'test.', 'test');

        $response = $this->withHeaders([
            'tilled-signature' => "v1=$signature,t=test",
        ])->post(route('tilled-webhook-listener'), [
            'type' => 'account.updated',
            'data' => [
                'id' => $id,
                'capabilities' => [
                    [
                        'status' => $status = fake()->randomElement(SubclientStatus::values()),
                    ],
                ],
            ],
        ]);

        $response->assertNoContent();
        $this->assertTrue($subclient->refresh()->status->value === $status);
    }

    #[Test]
    public function it_can_update_the_transaction_which_is_done_by_tilled_of_company(): void
    {
        $company = Company::factory()->create([
            'tilled_merchant_account_id' => $id = fake()->uuid(),
            'tilled_webhook_secret' => $secret = fake()->uuid(),
        ]);

        $user = User::factory()->for($company)->create(['subclient_id' => null]);

        $user->assignRole(Role::create(['name' => EnumRole::CREDITOR]));

        $ynTransaction = YnTransaction::factory()->for($company)->create();

        $transaction = Transaction::factory()
            ->has(ScheduleTransaction::factory()->state([
                'status' => TransactionStatus::SUCCESSFUL,
                'attempt_count' => 1,
                'last_attempted_at' => now(),
            ]))
            ->for($company)
            ->for(
                Consumer::factory()
                    ->has(ConsumerNegotiation::factory()->state([
                        'payment_plan_current_balance' => 0,
                        'active_negotiation' => true,
                    ]))
                    ->state([
                        'status' => ConsumerStatus::SETTLED,
                        'current_balance' => 0,
                        'has_failed_payment' => false,
                        'company_id' => $company->id,
                        'subclient_id' => null,
                    ])
            )
            ->create([
                'yn_transaction_id' => $ynTransaction->id,
                'status' => TransactionStatus::SUCCESSFUL,
                'subclient_id' => null,
            ]);

        config(['services.merchant.tilled_webhook_secret' => fake()->uuid()]);

        $signature = hash_hmac('sha256', "$secret.", $secret);

        $this->withHeaders([
            'tilled-signature' => "v1=$signature,t=$secret",
        ])
            ->post(route('tilled-webhook-listener'), [
                'type' => fake()->randomElement(['payment_intent.failed', 'payment_intent.canceled']),
                'data' => [
                    'id' => $transaction->transaction_id,
                    'account_id' => $id,
                ],
            ])
            ->assertNoContent();

        $this->assertEquals(TransactionStatus::FAILED, $transaction->refresh()->status);
        $this->assertNull($transaction->rnn_share_pass);
        $this->assertNull($transaction->yn_transaction_id);

        $this->assertEquals(TransactionStatus::FAILED, $transaction->scheduleTransactions()->first()->status);

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $transaction->consumer->status);
        $this->assertEquals($transaction->amount, $transaction->consumer->current_balance);
        $this->assertTrue($transaction->consumer->has_failed_payment);
        $this->assertEquals($transaction->amount, $transaction->consumer->consumerNegotiation->payment_plan_current_balance);

        $this->assertModelMissing($ynTransaction);
    }

    #[Test]
    public function it_can_update_company_when_tilled_webhook_come_with_completed_wizard_steps(): void
    {
        Mail::fake();

        $user = User::factory()->create(['subclient_id' => null]);

        $user->assignRole(Role::create(['name' => EnumRole::CREDITOR]));

        $user->company->update([
            'status' => CompanyStatus::SUBMITTED,
            'is_wizard_steps_completed' => false,
            'tilled_merchant_account_id' => $id = fake()->uuid(),
        ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::ABOUT_US],
                ['type' => CustomContentType::TERMS_AND_CONDITIONS]
            )
            ->create([
                'company_id' => $user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()
            ->create([
                'name' => fake()->word(),
                'company_id' => $user->company_id,
                'subclient_id' => null,
                'is_mapped' => true,
                'headers' => [
                    'EMAIL_ID' => ConsumerFields::CONSUMER_EMAIL->value,
                ],
            ]);

        config(['services.merchant.tilled_webhook_secret' => 'test']);

        $signature = hash_hmac('sha256', 'test.', 'test');

        $response = $this->withHeaders([
            'tilled-signature' => "v1=$signature,t=test",
        ])->post(route('tilled-webhook-listener'), [
            'type' => 'account.updated',
            'data' => [
                'id' => $id,
                'capabilities' => [
                    [
                        'status' => CompanyStatus::ACTIVE->value,
                    ],
                ],
            ],
        ]);

        $response->assertNoContent();
        $this->assertTrue($user->company->refresh()->status === CompanyStatus::ACTIVE);
        $this->assertEquals($user->company->is_wizard_steps_completed, 1);

        Mail::assertQueued(
            SetUpWizardStepsCompletedMail::class,
            fn (SetUpWizardStepsCompletedMail $mail) => $mail->assertTo($user->company->owner_email)
        );
    }
}
