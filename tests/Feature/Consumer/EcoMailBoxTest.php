<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Enums\ELetterType;
use App\Livewire\Consumer\EcoMailBox;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Models\ELetter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EcoMailBoxTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('consumer.e-letters'))
            ->assertSeeLivewire(EcoMailBox::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_component_with_correct_view(): void
    {
        Livewire::test(EcoMailBox::class)
            ->assertOk()
            ->assertViewIs('livewire.consumer.eco-mail-box')
            ->assertViewHas('consumerELetters', fn (LengthAwarePaginator $consumerELetters) => $consumerELetters->isEmpty());
    }

    #[Test]
    public function it_can_read_e_letter(): void
    {
        $consumerELetter = ConsumerELetter::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
                'read_by_consumer' => false,
            ]);

        Livewire::test(EcoMailBox::class)
            ->call('readByConsumer', $consumerELetter)
            ->assertOk();

        $this->assertTrue($consumerELetter->refresh()->read_by_consumer);
    }

    #[Test]
    public function it_can_call_delete_consumer_e_letter(): void
    {
        $consumerELetter = ConsumerELetter::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
                'read_by_consumer' => false,
            ]);

        Livewire::test(EcoMailBox::class)
            ->call('delete', $consumerELetter)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        $this->assertSoftDeleted($consumerELetter->refresh()->eLetter);
        $this->assertDatabaseCount(ConsumerELetter::class, 0);
        Notification::assertNotified(__('eLetter deleted.'));
    }

    #[Test]
    public function it_can_call_delete_consumer_e_letter_and_e_letter_have_multiply_consumers(): void
    {
        $eLetter = ELetter::factory()->create();

        $consumerELetters = ConsumerELetter::factory(3)
            ->create([
                'e_letter_id' => $eLetter->id,
                'consumer_id' => $this->consumer->id,
                'read_by_consumer' => false,
            ]);

        Livewire::test(EcoMailBox::class)
            ->call('delete', $consumerELetters->first())
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('eLetter deleted.'));

        $this->assertNotSoftDeleted($eLetter);
        $this->assertDatabaseCount(ConsumerELetter::class, 2);

        $this->assertDatabaseMissing(ConsumerELetter::class, ['id' => $consumerELetters->first()->id]);
    }

    #[Test]
    public function it_can_call_cfpb_e_letter_download(): void
    {
        $consumerELetter = ConsumerELetter::factory()
            ->for(ELetter::factory()->create(['type' => fake()->randomElement([ELetterType::CFPB_WITH_QR, ELetterType::CFPB_WITHOUT_QR])]))
            ->create([
                'consumer_id' => $this->consumer->id,
                'read_by_consumer' => false,
            ]);

        Livewire::test(EcoMailBox::class)
            ->call('downloadCFPBLetter', $consumerELetter)
            ->assertOk()
            ->assertFileDownloaded();

        Notification::assertNotified(__('eLetter downloaded.'));

        $this->assertTrue($consumerELetter->refresh()->read_by_consumer);
    }

    #[Test]
    public function it_can_search_by_company_name(): void
    {
        $createdConsumerELetters = ConsumerELetter::factory()
            ->forEachSequence(
                ['e_letter_id' => ELetter::factory()
                    ->for(Company::factory()->create(['company_name' => $companyName = 'test company']))
                    ->state([]),
                ],
                ['e_letter_id' => ELetter::factory()->state([])]
            )
            ->create([
                'consumer_id' => $this->consumer->id,
                'read_by_consumer' => false,
            ]);

        Livewire::withQueryParams(['search' => $companyName])
            ->test(EcoMailBox::class)
            ->assertViewHas('consumerELetters', function (LengthAwarePaginator $consumerELetters) use ($createdConsumerELetters): bool {
                return $consumerELetters->getCollection()->contains($createdConsumerELetters->first())
                    && $consumerELetters->getCollection()->doesntContain($createdConsumerELetters->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_filter_un_read_eco_mail(): void
    {
        $createdConsumerELetters = ConsumerELetter::factory()
            ->forEachSequence(
                ['read_by_consumer' => false],
                ['read_by_consumer' => true],
            )
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(EcoMailBox::class)
            ->set('only_read_by_consumer', true)
            ->assertOk()
            ->assertViewHas('consumerELetters', function (LengthAwarePaginator $consumerELetters) use ($createdConsumerELetters): bool {
                return $consumerELetters->getCollection()->contains($createdConsumerELetters->first())
                    && $consumerELetters->getCollection()->doesntContain($createdConsumerELetters->last());
            });
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_date(string $direction): void
    {
        $createdConsumerELetters = ConsumerELetter::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->subDays($sequence->index + 2),
            ])
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(EcoMailBox::class)
            ->assertOk()
            ->set('sortCol', 'created-at')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumerELetters',
                fn (LengthAwarePaginator $consumerELetters) => $direction === 'ASC'
                    ? $createdConsumerELetters->last()->is($consumerELetters->getCollection()->first())
                    : $createdConsumerELetters->first()->is($consumerELetters->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_sender(string $direction): void
    {
        $createdConsumerELetters = ConsumerELetter::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'e_letter_id' => ELetter::factory()
                    ->for(Company::factory()->create(['company_name' => range('A', 'Z')[$sequence->index]]))
                    ->state([]),
            ])
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(EcoMailBox::class)
            ->assertOk()
            ->set('sortCol', 'company-name')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'consumerELetters',
                fn (LengthAwarePaginator $consumerELetters) => $direction === 'ASC'
                    ? $createdConsumerELetters->first()->is($consumerELetters->getCollection()->first())
                    : $createdConsumerELetters->last()->is($consumerELetters->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_account_offer(string $direction): void
    {
        $createdConsumerELetters = ConsumerELetter::factory()
            ->forEachSequence(
                ['consumer_id' => Consumer::factory()
                    ->state(
                        [
                            'subclient_id' => null,
                            'status' => fake()->randomElement([ConsumerStatus::JOINED, ConsumerStatus::UPLOADED, ConsumerStatus::RENEGOTIATE]),
                            'last_name' => $this->consumer->last_name,
                            'last4ssn' => $this->consumer->last4ssn,
                            'dob' => $this->consumer->dob,
                        ]
                    ),
                ],
                ['consumer_id' => Consumer::factory()
                    ->state(
                        [
                            'subclient_id' => null,
                            'status' => ConsumerStatus::PAYMENT_ACCEPTED,
                            'last_name' => $this->consumer->last_name,
                            'last4ssn' => $this->consumer->last4ssn,
                            'dob' => $this->consumer->dob,
                        ]
                    ),
                ]
            )->create();

        Livewire::test(EcoMailBox::class)
            ->set('sortCol', 'account-offer')
            ->set('sortAsc', $direction === 'ASC')
            ->assertOk()
            ->assertViewHas(
                'consumerELetters',
                fn (LengthAwarePaginator $consumerELetters) => $direction === 'ASC'
                    ? $createdConsumerELetters->last()->is($consumerELetters->getCollection()->first())
                    : $createdConsumerELetters->first()->is($consumerELetters->getCollection()->first())
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
