<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard\Stats;

use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\Dashboard\Stats\Index;
use App\Models\Consumer;
use App\Models\PaymentProfile;
use App\Models\ScheduleTransaction;
use App\Models\Subclient;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.index')
            ->assertSet('stats', [
                'consumer' => [
                    'total_count' => 0,
                    'total_balance_count' => 0,
                    'accepted_count' => null,
                ],
                'scheduleTransaction' => [
                    'scheduled_payments' => null,
                    'failed_payments' => null,
                ],
                'transaction' => [
                    'successful_payments' => null,
                ],
            ])
            ->assertOk();

        $this->assertTrue(Cache::has("stats-{$this->user->id}"));
    }

    #[Test]
    public function it_can_render_the_data_with_some_stats(): void
    {
        Consumer::factory(3)->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::JOINED->value,
            'current_balance' => 30,
            'payment_setup' => false,
            'offer_accepted' => false,
        ]);

        $consumers = Consumer::factory(3)->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::JOINED->value,
            'current_balance' => 30,
            'payment_setup' => true,
            'offer_accepted' => true,
        ]);

        $paymentProfile = PaymentProfile::factory()->create();

        ScheduleTransaction::factory()
            ->forEachSequence(
                ['status' => TransactionStatus::SCHEDULED->value],
                ['status' => TransactionStatus::FAILED->value]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'consumer_id' => $consumers->first()->id,
                'payment_profile_id' => $paymentProfile->id,
                'schedule_date' => now()->addDays(3)->toDateString(),
                'amount' => 302.00,
                'last_attempted_at' => now()->subDays(5),
            ]);

        Transaction::query()->create([
            'company_id' => $this->user->company_id,
            'consumer_id' => $consumers->first()->id,
            'amount' => 402.03,
            'status' => TransactionStatus::SUCCESSFUL->value,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertViewIs('livewire.creditor.dashboard.stats.index')
            ->assertSet('stats', [
                'consumer' => [
                    'total_count' => 6,
                    'total_balance_count' => 180,
                    'accepted_count' => 3,
                ],
                'scheduleTransaction' => [
                    'scheduled_payments' => 302.00,
                    'failed_payments' => 302.00,
                ],
                'transaction' => [
                    'successful_payments' => 402.03,
                ],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_data_with_some_stats_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        Consumer::factory(3)->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'status' => ConsumerStatus::JOINED->value,
            'current_balance' => 30,
            'payment_setup' => false,
            'offer_accepted' => false,
        ]);

        $consumers = Consumer::factory(3)->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'status' => ConsumerStatus::JOINED->value,
            'current_balance' => 30,
            'payment_setup' => true,
            'offer_accepted' => true,
        ]);

        $paymentProfile = PaymentProfile::factory()->create();

        ScheduleTransaction::factory()
            ->forEachSequence(
                ['status' => TransactionStatus::SCHEDULED->value],
                ['status' => TransactionStatus::FAILED->value],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $subclient->id,
                'consumer_id' => $consumers->first()->id,
                'payment_profile_id' => $paymentProfile->id,
                'schedule_date' => now()->addDays(3)->toDateString(),
                'amount' => 382.00,
                'last_attempted_at' => now()->subDays(5),
            ]);

        Transaction::query()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'consumer_id' => $consumers->first()->id,
            'amount' => 412.03,
            'status' => TransactionStatus::SUCCESSFUL->value,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.dashboard.stats.index')
            ->assertSet('stats', [
                'consumer' => [
                    'total_count' => 6,
                    'total_balance_count' => 180,
                    'accepted_count' => 3,
                ],
                'scheduleTransaction' => [
                    'scheduled_payments' => 382.00,
                    'failed_payments' => 382.00,
                ],
                'transaction' => [
                    'successful_payments' => 412.03,
                ],
            ]);
    }
}
