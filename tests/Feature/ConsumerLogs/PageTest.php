<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerLogs;

use App\Livewire\Creditor\ConsumerLogs\Page;
use App\Models\Consumer;
use App\Models\ConsumerLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.consumer-logs.page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->getCollection()->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_display_consumers_with_its_activity(): void
    {
        $consumer = Consumer::factory()
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->has(ConsumerLog::factory(2))
            ->create();

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.consumer-logs.page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumer->is($consumers->getCollection()->first()))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_only_those_consumer_who_have_consumer_activities(): void
    {
        Consumer::factory()
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->create();

        $consumer = Consumer::factory()
            ->has(ConsumerLog::factory())
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->create();

        Livewire::actingAs($this->user)
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.consumer-logs.page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->total() === 1 && $consumer->is($consumers->getCollection()->first()))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_record_search_by_account_number(): void
    {
        $createdConsumers = Consumer::factory(2)
            ->sequence(
                ['account_number' => '12387'],
                ['account_number' => '12388']
            )
            ->has(ConsumerLog::factory(2))
            ->for($this->user->company)
            ->for($this->user->subclient)
            ->create();

        Livewire::actingAs($this->user)
            ->withQueryParams(['search' => '12388'])
            ->test(Page::class)
            ->assertViewIs('livewire.creditor.consumer-logs.page')
            ->assertViewHas('consumers', fn (LengthAwarePaginator $consumers) => $consumers->total() === 1 && $createdConsumers->last()->is($consumers->getCollection()->first()))
            ->assertOk();
    }
}
