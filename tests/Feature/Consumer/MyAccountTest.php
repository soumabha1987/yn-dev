<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\InstallmentType;
use App\Enums\NegotiationType;
use App\Livewire\Consumer\MyAccount;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\Reason;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\TestCase;

class MyAccountTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create(['status' => ConsumerStatus::JOINED]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');

        CompanyMembership::factory()->create(['company_id' => $this->consumer->company_id]);
    }

    #[Test]
    public function it_render_livewire_component_once_user_logged_in(): void
    {
        $this->get(route('consumer.account'))
            ->assertSeeLivewire(MyAccount::class)
            ->assertOk();
    }

    #[Test]
    public function it_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(MyAccount::class)
            ->assertViewIs('livewire.consumer.my-account')
            ->assertViewHas('consumerStatuses', fn (array $consumerStatuses) => Arr::has($consumerStatuses, 'all'))
            ->assertViewHas('accounts', fn (Collection $accounts) => $this->consumer->is($accounts->first()))
            ->assertOk();
    }

    #[Test]
    public function it_render_livewire_component_with_some_data(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::JOINED]);

        Consumer::factory()->create([
            'dob' => $this->consumer->dob->toDateString(),
            'last_name' => $this->consumer->last_name,
            'last4ssn' => $this->consumer->last4ssn,
            'status' => ConsumerStatus::SETTLED,
        ]);

        Livewire::test(MyAccount::class)
            ->assertViewIs('livewire.consumer.my-account')
            ->assertViewHas('consumerStatuses', fn (array $consumerStatuses) => count($consumerStatuses) === 3)
            ->assertViewHas('accounts', fn (Collection $accounts) => $accounts->count() === 2)
            ->assertSet('updateCommunicationModal', ! $this->consumer->consumerProfile->is_communication_updated)
            ->assertOk();
    }

    #[Test]
    public function it_can_ignore_and_continue_updated_communication(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::JOINED]);
        $this->consumer->consumerProfile()->update(['is_communication_updated' => false]);

        Livewire::test(MyAccount::class)
            ->assertViewIs('livewire.consumer.my-account')
            ->assertSet('updateCommunicationModal', true)
            ->call('ignoreCommunication')
            ->assertOk()
            ->assertSessionHas('consumer_' . $this->consumer->id . '_communication_ignored', true);
    }

    #[Test]
    public function it_can_assign_attributes_to_consumer_before_render(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::UPLOADED]);

        Consumer::factory()->create([
            'dob' => $this->consumer->dob->toDateString(),
            'last_name' => $this->consumer->last_name,
            'last4ssn' => $this->consumer->last4ssn,
            'status' => ConsumerStatus::JOINED,
        ]);

        $account = new stdClass;

        Livewire::test(MyAccount::class)
            ->assertSet('status', 'all')
            ->assertViewIs('livewire.consumer.my-account')
            ->assertViewHas('consumerStatuses', fn (array $consumerStatuses) => count($consumerStatuses) === 2)
            ->assertViewHas('accounts', function (Collection $accounts) use (&$account): bool {
                $account = $accounts->firstWhere('status', ConsumerStatus::JOINED);

                return $this->consumer->is($account);
            })
            ->assertOk();

        $this->assertArrayHasKey('company_name', $account->creditorDetails);
        $this->assertEquals($this->consumer->company->company_name, $account->creditorDetails['company_name']);

        $this->assertArrayHasKey('contact_person_name', $account->creditorDetails);
        $this->assertEquals($this->consumer->company->company_name, $account->creditorDetails['contact_person_name']);

        $this->assertArrayHasKey('custom_content', $account->creditorDetails);
        $this->assertEquals('N/A', $account->creditorDetails['custom_content']);

        $this->assertEquals(
            number_format((float) $account->current_balance, 2, '', ''),
            number_format($this->consumer->current_balance, 2, '', '')
        );

        $this->assertEquals('joined', $account->accountConditions);
        $this->assertNull($account->offerDetails);
        $this->assertNull($account->lastOffer);
    }

    #[Test]
    public function it_can_display_last_offer_send_by_consumer(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => false,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'negotiation_type' => NegotiationType::PIF,
            'offer_accepted' => true,
            'one_time_settlement' => 10.29,
            'payment_plan_current_balance' => 123.34,
            'installment_type' => InstallmentType::WEEKLY,
        ]);

        $lastOffer = [];

        Livewire::test(MyAccount::class)
            ->assertSet('status', 'all')
            ->assertViewIs('livewire.consumer.my-account')
            ->assertViewHas('consumerStatuses', fn (array $consumerStatuses) => count($consumerStatuses) === 2)
            ->assertViewHas('accounts', function (Collection $accounts) use (&$lastOffer): bool {
                $lastOffer = $accounts->first()->lastOffer;
                assert($accounts->first()->offerDetails === null);

                return $this->consumer->is($accounts->first());
            })
            ->assertOk();

        $this->assertIsArray($lastOffer);
        $this->assertArrayHasKey('account_profile_details', $lastOffer);
        $this->assertIsArray($lastOffer['account_profile_details']);

        $this->assertArrayHasKey('offer_summary', $lastOffer);
        $this->assertIsArray($lastOffer['offer_summary']);

        $this->assertArrayHasKey('account_number', $lastOffer['account_profile_details']);
        $this->assertEquals($this->consumer->account_number, $lastOffer['account_profile_details']['account_number']);

        $this->assertArrayHasKey('creditor_name', $lastOffer['account_profile_details']);
        $this->assertEquals($this->consumer->subclient->subclient_name, $lastOffer['account_profile_details']['creditor_name']);

        $this->assertArrayHasKey('current_balance', $lastOffer['account_profile_details']);
        $this->assertEquals(123.34, $lastOffer['account_profile_details']['current_balance']);

        $this->assertArrayHasKey('one_time_settlement', $lastOffer['offer_summary']);
        $this->assertEquals('$10.29', $lastOffer['offer_summary']['one_time_settlement']);

        $this->assertArrayHasKey('payment_setup_balance', $lastOffer['offer_summary']);
        $this->assertEquals(Number::currency((float) $consumerNegotiation->negotiate_amount), $lastOffer['offer_summary']['payment_setup_balance']);

        $this->assertArrayHasKey('plan_type', $lastOffer['offer_summary']);
        $this->assertEquals($consumerNegotiation->installment_type->displayName(), $lastOffer['offer_summary']['plan_type']);

        $this->assertArrayHasKey('my_offer', $lastOffer['offer_summary']);
        $this->assertEquals(Number::currency(((float) $consumerNegotiation->monthly_amount) * 4), $lastOffer['offer_summary']['my_offer']);

        $this->assertArrayHasKey('first_payment_date', $lastOffer['offer_summary']);
        $this->assertEquals($consumerNegotiation->first_pay_date->format('M d, Y'), $lastOffer['offer_summary']['first_payment_date']);
    }

    #[Test]
    public function it_can_display_counter_offer(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::PAYMENT_SETUP,
            'counter_offer' => true,
        ]);

        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
            'negotiation_type' => NegotiationType::INSTALLMENT,
            'offer_accepted' => true,
            'one_time_settlement' => null,
            'counter_one_time_amount' => null,
            'negotiate_amount' => 12.72,
            'payment_plan_current_balance' => 123.34,
            'installment_type' => InstallmentType::BIMONTHLY,
        ]);

        $offerDetails = [];

        Livewire::test(MyAccount::class)
            ->assertSet('status', 'all')
            ->assertViewIs('livewire.consumer.my-account')
            ->assertViewHas('consumerStatuses', fn (array $consumerStatuses) => count($consumerStatuses) === 2)
            ->assertViewHas('accounts', function (Collection $accounts) use (&$offerDetails): bool {
                $offerDetails = $accounts->first()->offerDetails;
                assert($accounts->first()->lastOffer === null);

                return $this->consumer->is($accounts->first());
            })
            ->assertOk();

        $this->assertArrayHasKey('account_profile_details', $offerDetails);
        $this->assertIsArray($offerDetails['account_profile_details']);

        $this->assertArrayHasKey('offer_summary', $offerDetails);
        $this->assertIsArray($offerDetails['offer_summary']);

        $this->assertArrayHasKey('account_number', $offerDetails['account_profile_details']);
        $this->assertEquals($this->consumer->account_number, $offerDetails['account_profile_details']['account_number']);

        $this->assertArrayHasKey('creditor_name', $offerDetails['account_profile_details']);
        $this->assertEquals($this->consumer->subclient->subclient_name, $offerDetails['account_profile_details']['creditor_name']);

        $this->assertArrayHasKey('current_balance', $offerDetails['account_profile_details']);
        $this->assertEquals(123.34, $offerDetails['account_profile_details']['current_balance']);

        $this->assertArrayHasKey('creditor_offer', $offerDetails['offer_summary']);
        $this->assertIsArray($offerDetails['offer_summary']['creditor_offer']);

        $this->assertArrayHasKey('one_time_settlement', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals($consumerNegotiation->counter_one_time_amount > 0 ? Number::currency((float) $consumerNegotiation->counter_one_time_amount) : 'N/A', $offerDetails['offer_summary']['creditor_offer']['one_time_settlement']);

        $this->assertArrayHasKey('payment_setup_balance', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals(Number::currency((float) $consumerNegotiation->counter_negotiate_amount), $offerDetails['offer_summary']['creditor_offer']['payment_setup_balance']);

        $this->assertArrayHasKey('plan_type', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals($consumerNegotiation->installment_type->displayName(), $offerDetails['offer_summary']['creditor_offer']['plan_type']);

        $this->assertArrayHasKey('counter_offer_amount', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals(Number::currency(((float) $consumerNegotiation->counter_monthly_amount) * 2), $offerDetails['offer_summary']['creditor_offer']['counter_offer_amount']);

        $this->assertArrayHasKey('first_payment_date', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals($consumerNegotiation->counter_first_pay_date->format('M d, Y'), $offerDetails['offer_summary']['creditor_offer']['first_payment_date']);

        $this->assertArrayHasKey('counter_note', $offerDetails['offer_summary']['creditor_offer']);
        $this->assertEquals($consumerNegotiation->counter_note, $offerDetails['offer_summary']['creditor_offer']['counter_note']);

        $this->assertArrayHasKey('my_last_offer', $offerDetails['offer_summary']);
        $this->assertIsArray($offerDetails['offer_summary']['my_last_offer']);

        $this->assertArrayHasKey('one_time_settlement', $offerDetails['offer_summary']['my_last_offer']);
        $this->assertEquals('N/A', $offerDetails['offer_summary']['my_last_offer']['one_time_settlement']);

        $this->assertArrayHasKey('payment_setup_balance', $offerDetails['offer_summary']['my_last_offer']);
        $this->assertEquals('$12.72', $offerDetails['offer_summary']['my_last_offer']['payment_setup_balance']);

        $this->assertArrayHasKey('plan_type', $offerDetails['offer_summary']['my_last_offer']);
        $this->assertEquals($consumerNegotiation->installment_type->displayName(), $offerDetails['offer_summary']['my_last_offer']['plan_type']);

        $this->assertArrayHasKey('my_offer', $offerDetails['offer_summary']['my_last_offer']);
        $this->assertEquals(Number::currency(((float) $consumerNegotiation->monthly_amount) * 2), $offerDetails['offer_summary']['my_last_offer']['my_offer']);

        $this->assertArrayHasKey('first_payment_date', $offerDetails['offer_summary']['my_last_offer']);
        $this->assertEquals($consumerNegotiation->first_pay_date->format('M d, Y'), $offerDetails['offer_summary']['my_last_offer']['first_payment_date']);
    }

    #[Test]
    public function it_can_not_dispute_consumer(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED,
        ]);

        Livewire::test(MyAccount::class)
            ->call('dispute', $consumer)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertNotEquals(ConsumerStatus::DISPUTE, $consumer->refresh()->status);
        $this->assertNull($consumer->disputed_at);
    }

    #[Test]
    public function it_can_dispute_consumer(): void
    {
        $consumer = Consumer::factory()->create([
            'last_name' => $this->consumer->last_name,
            'last4ssn' => $this->consumer->last4ssn,
            'dob' => $this->consumer->dob,
            'status' => ConsumerStatus::JOINED,
        ]);

        Livewire::test(MyAccount::class)
            ->assertViewIs('livewire.consumer.my-account')
            ->call('dispute', $consumer)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertEquals(ConsumerStatus::DISPUTE, $consumer->refresh()->status);
        $this->assertEquals(now()->toDateString(), $consumer->disputed_at->toDateString());
    }

    #[Test]
    public function it_start_over_and_delete_the_consumer_negotiations(): void
    {
        $consumerNegotiation = ConsumerNegotiation::factory()->create([
            'consumer_id' => $this->consumer->id,
        ]);

        $this->consumer->update([
            'counter_offer' => true,
            'offer_accepted' => true,
            'payment_setup' => true,
            'has_failed_payment' => true,
            'custom_offer' => true,
        ]);

        Livewire::test(MyAccount::class)
            ->assertSet('status', 'all')
            ->call('startOver', $this->consumer)
            ->assertOk()
            ->assertRedirectToRoute('consumer.negotiate', ['consumer' => $this->consumer->id]);

        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->refresh()->status);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertFalse($this->consumer->offer_accepted);
        $this->assertFalse($this->consumer->payment_setup);
        $this->assertFalse($this->consumer->has_failed_payment);
        $this->assertFalse($this->consumer->custom_offer);

        $this->assertModelMissing($consumerNegotiation);
    }

    #[Test]
    public function it_can_update_the_status_and_display_accordingly(): void
    {
        Livewire::test(MyAccount::class)
            ->assertSet('status', 'all')
            ->assertViewHas('accounts', fn (Collection $accounts) => $accounts->isNotEmpty())
            ->tap(function (Testable $test): void {
                $consumer = Consumer::factory()
                    ->has(ConsumerNegotiation::factory())
                    ->create([
                        'dob' => $this->consumer->dob,
                        'last_name' => $this->consumer->last_name,
                        'last4ssn' => $this->consumer->last4ssn,
                        'status' => ConsumerStatus::PAYMENT_SETUP,
                    ]);

                $test->call('updateStatus', 'active_negotiation')
                    ->assertViewHas('accounts', fn (Collection $accounts): bool => $consumer->is($accounts->first()) && $accounts->containsOneItem())
                    ->assertOk();
            })
            ->assertOk();
    }

    #[Test]
    public function it_download_agreement_of_consumer(): void
    {
        Pdf::shouldReceive('setOption')->once()->andReturnSelf();
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('output')->once()->andReturn('Hello');

        Livewire::test(MyAccount::class)
            ->call('downloadAgreement', $this->consumer)
            ->assertFileDownloaded($this->consumer->account_number . '_you_negotiate_agreement.pdf')
            ->assertOk();
    }

    #[Test]
    public function it_can_display_not_paying_consumer(): void
    {
        $reason = Reason::factory()->create();

        $this->consumer->update([
            'status' => ConsumerStatus::NOT_PAYING,
            'reason_id' => $reason->id,
        ]);

        Livewire::test(MyAccount::class)
            ->assertSee($this->consumer->original_account_name)
            ->assertSee(str($reason->label)->limit(20))
            ->assertSee(__('Restart'));
    }

    #[Test]
    public function it_can_call_restart_consumer(): void
    {
        $reason = Reason::factory()->create(['is_system' => false]);

        $this->consumer->update([
            'status' => ConsumerStatus::NOT_PAYING,
            'reason_id' => $reason->id,
        ]);

        Livewire::test(MyAccount::class)
            ->assertSee($this->consumer->original_account_name)
            ->assertSee(str($reason->label)->limit(20))
            ->assertSee(__('Restart'))
            ->call('restart', $this->consumer)
            ->assertOk()
            ->assertDontSee(__('Restart'));

        Notification::assertNotified(__('Your account successfully restart'));

        $this->assertNull($this->consumer->refresh()->reason_id);
        $this->assertEquals(ConsumerStatus::JOINED, $this->consumer->status);
        $this->assertNull($this->consumer->disputed_at);
        $this->assertModelMissing($reason);
    }

    #[Test]
    public function it_can_call_restart_different_un_match_consumer(): void
    {
        $consumer = Consumer::factory()
            ->for(Reason::factory()->create())
            ->create([
                'status' => ConsumerStatus::NOT_PAYING,
                'disputed_at' => now(),
            ]);

        Livewire::test(MyAccount::class)
            ->call('restart', $consumer)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Sorry this consumer does not match credential'));

        $this->assertNotNull($consumer->refresh()->reason_id);
        $this->assertEquals(ConsumerStatus::NOT_PAYING, $consumer->status);
        $this->assertNotNull($consumer->disputed_at);
    }
}
