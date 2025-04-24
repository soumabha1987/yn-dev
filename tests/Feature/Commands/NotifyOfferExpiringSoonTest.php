<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\NotifyOfferExpiringSoon;
use App\Enums\CommunicationCode;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifyOfferExpiringSoonTest extends TestCase
{
    #[Test]
    public function it_can_send_consumer_offer_expiration_reminder(): void
    {
        Queue::fake();

        $consumerData = [
            'offer_accepted' => true,
            'payment_setup' => false,
        ];

        ConsumerNegotiation::factory()
            ->forEachSequence(
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => true,
                    'counter_offer_accepted' => false,
                    'first_pay_date' => today()->addDay()->toDateString(),
                    'counter_first_pay_date' => today()->subDay()->toDateString(),
                ],
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->subDay()->toDateString(),
                    'counter_first_pay_date' => today()->addDays(3)->toDateString(),
                ],
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->addDays(3)->toDateString(),
                    'counter_first_pay_date' => today()->addDay()->toDateString(),
                ],
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => true,
                    'counter_offer_accepted' => false,
                    'first_pay_date' => today()->addDays(3)->toDateString(),
                    'counter_first_pay_date' => today()->addDay()->toDateString(),
                ],
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->addDays(3)->toDateString(),
                    'counter_first_pay_date' => today()->addDays(2)->toDateString(),
                ],
                [
                    'consumer_id' => Consumer::factory()->state($consumerData),
                    'offer_accepted' => true,
                    'counter_offer_accepted' => false,
                    'first_pay_date' => today()->addDays(2)->toDateString(),
                    'counter_first_pay_date' => today()->addDays(3)->toDateString(),
                ],
            )
            ->create();

        $this->artisan(NotifyOfferExpiringSoon::class)->assertOk();

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 4);
    }

    #[Test]
    #[DataProvider('consumerNegotiationData')]
    public function it_can_send_consumer_offer_expiration_reminder_with_checked_communication_code(array $consumerNegotiationData, CommunicationCode $communicationCode): void
    {
        Queue::fake();

        ConsumerNegotiation::factory()
            ->for($consumer = Consumer::factory()->create([
                'offer_accepted' => true,
                'payment_setup' => false,
            ]))
            ->create($consumerNegotiationData);

        $this->artisan(NotifyOfferExpiringSoon::class)->assertOk();

        Queue::assertPushed(
            TriggerEmailAndSmsServiceJob::class,
            fn (TriggerEmailAndSmsServiceJob $job): bool => $consumer->is((fn () => $this->{'consumer'})->call($job))
                && (fn () => $this->{'communicationCode'})->call($job) === $communicationCode
        );
    }

    public static function consumerNegotiationData(): array
    {
        return [
            'Offer accepted and one day expiration date reminder' => [
                [
                    'offer_accepted' => true,
                    'counter_offer_accepted' => false,
                    'first_pay_date' => today()->addDay()->toDateString(),
                    'counter_first_pay_date' => today()->subDay()->toDateString(),
                ],
                CommunicationCode::ONE_DAY_EXPIRATION_DATE_REMINDER,
            ],
            'Offer accepted and three day expiration date reminder' => [
                [
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->subDay()->toDateString(),
                    'counter_first_pay_date' => today()->addDays(3)->toDateString(),
                ],
                CommunicationCode::THREE_DAY_EXPIRATION_DATE_REMINDER,
            ],
            'Counter offer accepted and three day expiration date reminder' => [
                [
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->addDays(3)->toDateString(),
                    'counter_first_pay_date' => today()->addDays(3)->toDateString(),
                ],
                CommunicationCode::THREE_DAY_EXPIRATION_DATE_REMINDER,
            ],
            'Counter offer accepted and one day expiration date reminder' => [
                [
                    'offer_accepted' => false,
                    'counter_offer_accepted' => true,
                    'first_pay_date' => today()->addDays(3)->toDateString(),
                    'counter_first_pay_date' => today()->addDay()->toDateString(),
                ],
                CommunicationCode::ONE_DAY_EXPIRATION_DATE_REMINDER,
            ],
        ];
    }
}
