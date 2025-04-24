<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\MerchantType;
use App\Enums\TransactionStatus;
use App\Livewire\Consumer\PaymentHistory;
use App\Models\Consumer;
use App\Models\PaymentProfile;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->has(PaymentProfile::factory()->state(['method' => MerchantType::CC, 'last4digit' => '5346']))
            ->create([
                'status' => ConsumerStatus::DEACTIVATED,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    #[DataProvider('render')]
    public function it_can_redirect_if_there_is_no_payment_profile_or_consumer_status_is_not_deactivated(Closure $render): void
    {
        $render($this->consumer);

        $this->get(route('consumer.payment_history', ['consumer' => $this->consumer]))
            ->assertRedirectToRoute('consumer.account');
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::test(PaymentHistory::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.payment-history');
    }

    #[Test]
    public function it_can_check_subclient_creditor_details_are_set(): void
    {
        Livewire::test(PaymentHistory::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.payment-history')
            ->assertViewHas('transactions', fn (Collection $transactions) => $transactions->isEmpty())
            ->assertSet('creditorDetails.company_name', $this->consumer->company->company_name)
            ->assertSet('creditorDetails.contact_person_name', $this->consumer->company->company_name)
            ->assertSet('creditorDetails.custom_content', 'N/A');
    }

    #[Test]
    public function it_can_render_livewire_component_with_some_data(): void
    {
        $transaction = Transaction::factory()
            ->for($this->consumer)
            ->for($this->consumer->company)
            ->for($this->consumer->subclient)
            ->for($this->consumer->paymentProfile)
            ->create([
                'amount' => 187.2,
                'status' => TransactionStatus::SUCCESSFUL,
            ]);

        Livewire::test(PaymentHistory::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewHas('transactions', fn (Collection $transactions) => $transaction->is($transactions->first()))
            ->assertSee('$187.2')
            ->assertSee('Transaction Successful')
            ->assertSee('CARD (xx-5346)');
    }

    #[Test]
    public function it_download_agreement_of_consumer(): void
    {
        Pdf::shouldReceive('setOption')->once()->andReturnSelf();
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('output')->once()->andReturn('Hello');

        Livewire::test(PaymentHistory::class, ['consumer' => $this->consumer])
            ->call('downloadAgreement', $this->consumer)
            ->assertFileDownloaded($this->consumer->account_number . '_you_negotiate_agreement.pdf')
            ->assertOk();
    }

    public static function render(): array
    {
        return [
            [fn (Consumer $consumer) => $consumer->update(['status' => ConsumerStatus::JOINED])],
            [fn (Consumer $consumer) => $consumer->paymentProfile()->delete()],
        ];
    }
}
