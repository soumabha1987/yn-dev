<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\MyAccount\ReportNotPaying;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\Reason;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportNotPayingTest extends TestCase
{
    protected Company $company;

    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->consumer = Consumer::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::JOINED,
            ]);
    }

    #[Test]
    public function it_can_update_the_reason_why_consumer_is_not_paying(): void
    {
        $reason = Reason::factory()->create(['is_system' => true]);

        $this->assertNull($this->consumer->disputed_at);

        Livewire::test(ReportNotPaying::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewHas('reasons', fn (Collection $reasons) => $reasons->isNotEmpty() && $reasons->first() === $reason->label)
            ->set('reason', $reason->id)
            ->assertSeeHtml('wire:submit="reportNotPaying"')
            ->call('reportNotPaying')
            ->assertHasNoErrors()
            ->assertRedirect(route('consumer.account'));

        Notification::assertNotified(__('We have updated your account status and sent your response to the hosting YN member.'));

        $this->assertEquals(ConsumerStatus::NOT_PAYING, $this->consumer->refresh()->status);
        $this->assertEquals($reason->id, $this->consumer->reason_id);
        $this->assertNotNull($this->consumer->disputed_at);
    }

    #[Test]
    public function it_can_create_the_reason_why_consumer_is_not_paying(): void
    {
        $reason = Reason::factory()
            ->create([
                'label' => 'Other',
                'is_system' => true,
            ]);

        $this->assertNull($this->consumer->disputed_at);

        Livewire::test(ReportNotPaying::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewHas('reasons', fn (Collection $reasons) => $reasons->isNotEmpty() && $reasons->first() === $reason->label)
            ->set('reason', $reason->id)
            ->set('other', 'Test New Reason')
            ->assertSeeHtml('wire:submit="reportNotPaying"')
            ->call('reportNotPaying')
            ->assertHasNoErrors()
            ->assertRedirect(route('consumer.account'));

        Notification::assertNotified(__('We have updated your account status and sent your response to the hosting YN member.'));
        $this->assertDatabaseHas(Reason::class, ['label' => 'Test New Reason']);
        $this->assertDatabaseCount(Reason::class, 2);
        $this->assertEquals(ConsumerStatus::NOT_PAYING, $this->consumer->refresh()->status);
        $this->assertNotNull($this->consumer->disputed_at);
    }

    #[Test]
    public function it_can_call_report_not_paying_consumer_have_consumer_negotiation(): void
    {
        $reason = Reason::factory()->create(['is_system' => true]);

        $consumerNegotiation = ConsumerNegotiation::factory()->for($this->consumer)->create();

        $this->assertNull($this->consumer->disputed_at);

        Livewire::test(ReportNotPaying::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewHas('reasons', fn (Collection $reasons) => $reasons->isNotEmpty() && $reasons->first() === $reason->label)
            ->set('reason', $reason->id)
            ->assertSeeHtml('wire:submit="reportNotPaying"')
            ->call('reportNotPaying')
            ->assertHasNoErrors()
            ->assertRedirect(route('consumer.account'));

        Notification::assertNotified(__('We have updated your account status and sent your response to the hosting YN member.'));

        $this->assertEquals(ConsumerStatus::NOT_PAYING, $this->consumer->refresh()->status);
        $this->assertEquals($reason->id, $this->consumer->reason_id);
        $this->assertNotNull($this->consumer->disputed_at);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertFalse($this->consumer->custom_offer);
        $this->assertFalse($this->consumer->payment_setup);

        $this->assertModelMissing($consumerNegotiation);
    }
}
