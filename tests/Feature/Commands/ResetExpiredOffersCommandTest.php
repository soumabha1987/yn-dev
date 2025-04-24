<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\ResetExpiredOffersCommand;
use App\Enums\ConsumerStatus;
use App\Enums\TransactionStatus;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ScheduleTransaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetExpiredOffersCommandTest extends TestCase
{
    #[Test]
    public function it_can_consumer_first_pay_date_expired(): void
    {
        $consumer = Consumer::factory()->create([
            'offer_accepted' => true,
            'payment_setup' => false,
        ]);

        $scheduleTransactions = ScheduleTransaction::factory()
            ->for($consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $consumerNegotiate = ConsumerNegotiation::factory()
            ->for($consumer)
            ->create([
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'first_pay_date' => today()->subDay()->toDateString(),
            ]);

        $this->artisan(ResetExpiredOffersCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::JOINED, $consumer->refresh()->status);
        $this->assertFalse($consumer->counter_offer);
        $this->assertFalse($consumer->offer_accepted);
        $this->assertFalse($consumer->payment_setup);
        $this->assertFalse($consumer->has_failed_payment);
        $this->assertFalse($consumer->custom_offer);

        $this->assertModelMissing($consumerNegotiate);
        $this->assertModelMissing($scheduleTransactions);
    }

    #[Test]
    public function it_can_consumer_counter_first_pay_date_expired(): void
    {
        $consumer = Consumer::factory()->create([
            'offer_accepted' => true,
            'payment_setup' => false,
        ]);

        $scheduleTransactions = ScheduleTransaction::factory()
            ->for($consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $consumerNegotiate = ConsumerNegotiation::factory()
            ->for($consumer)
            ->create([
                'offer_accepted' => false,
                'counter_offer_accepted' => true,
                'counter_first_pay_date' => today()->subDay()->toDateString(),
            ]);

        $this->artisan(ResetExpiredOffersCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::JOINED, $consumer->refresh()->status);
        $this->assertFalse($consumer->counter_offer);
        $this->assertFalse($consumer->offer_accepted);
        $this->assertFalse($consumer->payment_setup);
        $this->assertFalse($consumer->has_failed_payment);
        $this->assertFalse($consumer->custom_offer);

        $this->assertModelMissing($consumerNegotiate);
        $this->assertModelMissing($scheduleTransactions);
    }

    #[Test]
    public function it_can_consumer_first_pay_date_not_expired(): void
    {
        $consumer = Consumer::factory()->create([
            'offer_accepted' => true,
            'payment_setup' => false,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        $scheduleTransactions = ScheduleTransaction::factory()
            ->for($consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $consumerNegotiate = ConsumerNegotiation::factory()
            ->for($consumer)
            ->create([
                'offer_accepted' => true,
                'counter_offer_accepted' => false,
                'first_pay_date' => today()->addDay()->toDateString(),
                'counter_first_pay_date' => today()->subDay()->toDateString(),
            ]);

        $this->artisan(ResetExpiredOffersCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $consumer->refresh()->status);
        $this->assertTrue($consumer->offer_accepted);
        $this->assertFalse($consumer->payment_setup);

        $this->assertModelExists($consumerNegotiate);
        $this->assertModelExists($scheduleTransactions);
    }

    #[Test]
    public function it_can_consumer_counter_first_pay_date_not_expired(): void
    {
        $consumer = Consumer::factory()->create([
            'offer_accepted' => true,
            'payment_setup' => false,
            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
        ]);

        $scheduleTransactions = ScheduleTransaction::factory()
            ->for($consumer)
            ->create(['status' => TransactionStatus::SCHEDULED]);

        $consumerNegotiate = ConsumerNegotiation::factory()
            ->for($consumer)
            ->create([
                'offer_accepted' => false,
                'counter_offer_accepted' => true,
                'first_pay_date' => today()->subDay()->toDateString(),
                'counter_first_pay_date' => today()->addDay()->toDateString(),
            ]);

        $this->artisan(ResetExpiredOffersCommand::class)->assertOk();

        $this->assertEquals(ConsumerStatus::PAYMENT_ACCEPTED, $consumer->refresh()->status);
        $this->assertTrue($consumer->offer_accepted);
        $this->assertFalse($consumer->payment_setup);

        $this->assertModelExists($consumerNegotiate);
        $this->assertModelExists($scheduleTransactions);
    }
}
