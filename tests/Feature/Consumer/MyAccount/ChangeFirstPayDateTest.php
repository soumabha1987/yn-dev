<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\MyAccount;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\MyAccount\ChangeFirstPayDate;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeFirstPayDateTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(Company::factory()->create())
            ->create([
                'subclient_id' => null,
                'status' => ConsumerStatus::PAYMENT_SETUP,
                'counter_offer' => true,
                'current_balance' => 100,
                'pay_setup_discount_percent' => 30,
                'min_monthly_pay_percent' => 25,
                'max_days_first_pay' => 20,
            ]);

        $this->withoutVite()->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view_and_data(): void
    {
        $consumerNegotiation = ConsumerNegotiation::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        $first_pay_date = '';

        if ($consumerNegotiation->offer_accepted) {
            $first_pay_date = $consumerNegotiation->first_pay_date->toDateString();
        }

        if ($consumerNegotiation->counter_offer_accepted) {
            $first_pay_date = $consumerNegotiation->counter_first_pay_date?->toDateString();
        }

        Livewire::test(ChangeFirstPayDate::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertViewIs('livewire.consumer.my-account.change-first-pay-date')
            ->assertViewHas('consumer', fn (Consumer $consumer): bool => $this->consumer->is($consumer))
            ->assertSet('first_pay_date', $first_pay_date);
    }

    #[Test]
    public function it_can_update_consumer_first_pay_date(): void
    {
        $consumerNegotiation = ConsumerNegotiation::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(ChangeFirstPayDate::class, ['consumer' => $this->consumer])
            ->set('first_pay_date', $firstPayDate = today()->addDays(fake()->numberBetween(1, 15))->toDateString())
            ->call('changeFirstPayDate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog');

        Notification::assertNotified('Your first pay date update successfully.');

        $consumerNegotiation->refresh();

        $this->assertEquals($firstPayDate, $consumerNegotiation->offer_accepted
            ? $consumerNegotiation->first_pay_date->toDateString()
            : $consumerNegotiation->counter_first_pay_date->toDateString());
    }

    #[Test]
    public function it_can_update_first_pay_date_after_auto_approved_date(): void
    {
        $consumerNegotiation = ConsumerNegotiation::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(ChangeFirstPayDate::class, ['consumer' => $this->consumer])
            ->set('first_pay_date', $firstPayDate = today()->addDays(fake()->numberBetween(21, 100))->toDateString())
            ->call('changeFirstPayDate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog');

        Notification::assertNotified('Awesome! Your offer was sent to your creditor!');

        $this->assertEquals($firstPayDate, $consumerNegotiation->refresh()->first_pay_date->toDateString());
        $this->assertFalse($consumerNegotiation->offer_accepted);
        $this->assertNull($consumerNegotiation->counter_first_pay_date);
        $this->assertFalse($consumerNegotiation->counter_offer_accepted);
        $this->assertNull($consumerNegotiation->counter_one_time_amount);
        $this->assertNull($consumerNegotiation->counter_monthly_amount);
        $this->assertNull($consumerNegotiation->counter_last_month_amount);
        $this->assertNull($consumerNegotiation->counter_no_of_installments);
        $this->assertNull($consumerNegotiation->counter_note);
        $this->assertNull($consumerNegotiation->counter_negotiate_amount);

        $this->assertFalse($this->consumer->refresh()->offer_accepted);
        $this->assertFalse($this->consumer->counter_offer);
        $this->assertTrue($this->consumer->custom_offer);
        $this->assertEquals(ConsumerStatus::PAYMENT_SETUP, $this->consumer->status);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_update_first_pay_date_validation_errors(array $requestSetData, array $requestErrors): void
    {
        ConsumerNegotiation::factory()
            ->create([
                'consumer_id' => $this->consumer->id,
            ]);

        Livewire::test(ChangeFirstPayDate::class, ['consumer' => $this->consumer])
            ->set($requestSetData)
            ->call('changeFirstPayDate')
            ->assertOk()
            ->assertHasErrors($requestErrors)
            ->assertNotDispatched('close-dialog');
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'first_pay_date' => '',
                ],
                [
                    'first_pay_date' => ['required'],
                ],
            ],
            [
                [
                    'first_pay_date' => str('a')->repeat(300),
                ],
                [
                    'first_pay_date' => ['date'],
                ],
            ],
            [
                [
                    'first_pay_date' => today()->toDateString(),
                ],
                [
                    'first_pay_date' => ['after:today'],
                ],
            ],
            [
                [
                    'first_pay_date' => today()->addDay()->format('m/d/Y'),
                ],
                [
                    'first_pay_date' => ['date_format:Y-m-d'],
                ],
            ],
        ];
    }
}
