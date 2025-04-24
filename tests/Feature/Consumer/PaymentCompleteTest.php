<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use AllowDynamicProperties;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionType;
use App\Livewire\Consumer\PaymentComplete;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\PaymentProfile;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class PaymentCompleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->create([
                'status' => ConsumerStatus::SETTLED,
            ]);

        $this->paymentProfile = PaymentProfile::factory()
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->create();

        $this->consumerNegotiation = ConsumerNegotiation::factory()
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->create([
                'offer_accepted' => true,
                'one_time_settlement' => 129.67,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_redirect_to_my_account_page_because_consumer_is_not_settled(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::JOINED]);

        $this->get(route('consumer.complete_payment', ['consumer' => $this->consumer]))
            ->assertRedirectToRoute('consumer.account')
            ->assertStatus(302);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('consumer.complete_payment', ['consumer' => $this->consumer]))
            ->assertSeeLivewire(PaymentComplete::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(PaymentComplete::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.payment-complete')
            ->assertViewHas('transactions', fn (Collection $transactions) => $transactions->isEmpty())
            ->assertViewHas('consumer', fn (Consumer $consumer) => $consumer->is($this->consumer))
            ->assertViewHas('consumer.paymentProfile', fn (PaymentProfile $paymentProfile) => $paymentProfile->is($this->consumer->paymentProfile))
            ->assertViewHas('consumer.consumerNegotiation', fn (ConsumerNegotiation $consumerNegotiation) => $consumerNegotiation->is($this->consumer->consumerNegotiation))
            ->assertSet('creditorDetails.company_name', $this->consumer->company->company_name)
            ->assertSet('creditorDetails.custom_content', 'N/A')
            ->assertSee(__('Payment Paid To'))
            ->assertSee(__('Account Information'))
            ->assertSee($this->consumer->first_name . ' ' . $this->consumer->last_name)
            ->assertSee($this->consumer->member_account_number ?? 'N/A')
            ->assertSee(Number::currency((float) $this->consumer->total_balance))
            ->assertSee($this->consumer->original_account_name);
    }

    #[Test]
    public function it_can_download_agreement(): void
    {
        Livewire::test(PaymentComplete::class, ['consumer' => $this->consumer])
            ->call('downloadAgreement', $this->consumer)
            ->assertOk()
            ->assertFileDownloaded($this->consumer->account_number . '_you_negotiate_agreement.pdf');
    }

    #[Test]
    public function it_can_render_with_transactions(): void
    {
        $transaction = Transaction::factory()
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->for($this->consumer->paymentProfile)
            ->create([
                'transaction_type' => TransactionType::PARTIAL_PIF,
            ]);

        Livewire::test(PaymentComplete::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertSee(Number::currency((float) $this->consumer->total_balance))
            ->assertSee(Number::currency((float) $transaction->sum('amount')))
            ->assertViewHas('transactions.0', fn (Transaction $viewTransaction) => $viewTransaction->is($transaction));
    }
}
