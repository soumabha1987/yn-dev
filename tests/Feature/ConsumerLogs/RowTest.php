<?php

declare(strict_types=1);

namespace Tests\Feature\ConsumerLogs;

use App\Livewire\Creditor\ConsumerLogs\Row;
use App\Models\Consumer;
use App\Models\ConsumerLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RowTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::test(Row::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.consumer-logs.row')
            ->assertViewHas('consumer', $this->consumer)
            ->assertViewHas('consumerLogs', fn (LengthAwarePaginator $consumerLogs) => $consumerLogs->getCollection()->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $consumerLog = ConsumerLog::factory()->for($this->consumer)->create();

        Livewire::test(Row::class, ['consumer' => $this->consumer])
            ->assertViewHas('consumerLogs', fn (LengthAwarePaginator $consumerLogs) => $consumerLog->is($consumerLogs->getCollection()->first()))
            ->assertSee($consumerLog->created_at->formatWithTimezone(format: 'M, d Y H:i:s'))
            ->assertSee($consumerLog->log_message)
            ->assertOk();
    }
}
