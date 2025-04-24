<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\ConsumerStatus;
use App\Livewire\Creditor\Dashboard\DisputeReports;
use App\Models\Consumer;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisputeReportsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['subclient_id' => null]);
    }

    #[Test]
    public function it_can_render_livewire_component_with_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertViewIs('livewire.creditor.dashboard.dispute-reports')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_consumer_data(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            'disputed_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertSee($consumer->disputed_at->formatWithTimezone(format: 'M d, Y h:i A'))
            ->assertSee($consumer->original_account_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_consumer_data_for_subclient(): void
    {
        $subclient = Subclient::factory()->for($this->user->company)->create();

        $this->user->update(['subclient_id' => $subclient->id]);

        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'status' => ConsumerStatus::DISPUTE,
        ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertSee($consumer->member_account_number)
            ->assertSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertOk();
    }

    #[Test]
    public function it_can_export_dispute_consumers(): void
    {
        Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::DISPUTE,
        ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    public function it_can_render_deactivated_consumer(): void
    {
        $consumer = Consumer::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => ConsumerStatus::DEACTIVATED,
        ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->isNot($consumers->getCollection()->first()))
            ->assertDontSee($consumer->account_number)
            ->assertDontSee($consumer->first_name . ' ' . $consumer->last_name)
            ->assertDontSee($consumer->status->displayLabel())
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_disputed_at(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->last()->is($consumers->getCollection()->first())
                    : $createdConsumers->first()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_balance(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'current_balance' => 100 + ($sequence->index * 10),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'account_balance')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_consumer_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'first_name' => range('A', 'Z')[$sequence->index + 1],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'consumer_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_original_account_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'original_account_name' => range('A', 'Z')[$sequence->index + 1],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_account_number(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'member_account_number' => $sequence->index + 1000,
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'account_number')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sub_account_name(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'subclient_name' => range('a', 'z')[$sequence->index + 2],
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'sub_account_name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->first()->is($consumers->getCollection()->first())
                    : $createdConsumers->last()->is($consumers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_placement_date(string $direction): void
    {
        $createdConsumers = Consumer::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'disputed_at' => now()->subDays($sequence->index + 2),
                'placement_date' => now()->subDays($sequence->index + 2),
            ])
            ->create([
                'company_id' => $this->user->company_id,
                'status' => fake()->randomElement([ConsumerStatus::NOT_PAYING, ConsumerStatus::DISPUTE]),
            ]);

        Livewire::actingAs($this->user)
            ->test(DisputeReports::class)
            ->assertOk()
            ->set('sortCol', 'placement_date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumers',
                fn (LengthAwarePaginator $consumers) => $direction === 'ASC'
                    ? $createdConsumers->last()->is($consumers->getCollection()->first())
                    : $createdConsumers->first()->is($consumers->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}
