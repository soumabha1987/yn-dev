<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers\ConsumerProfile;

use App\Enums\ELetterType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManageConsumers\ConsumerProfile\ELetterHistories;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Models\ELetter;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

/**
 * This nesting component testing doesn't required Authentication.
 */
class ELetterHistoriesTest extends AuthTestCase
{
    public Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();
    }

    #[Test]
    public function it_can_render_e_letters_histories(): void
    {
        Livewire::test(ELetterHistories::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.e-letter-histories')
            ->assertViewHas('eLetters', fn (LengthAwarePaginator $eLetters) => $eLetters->getCollection()->isEmpty())
            ->assertSee(__('No result found'));
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        Livewire::test(ELetterHistories::class, ['consumer' => $this->consumer])
            ->assertSee(__('Company Name'))
            ->assertSee(__('Subclient Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_cancelled_schedule_transactions_view_when_user_creditor(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        Livewire::test(ELetterHistories::class, ['consumer' => $this->consumer])
            ->assertDontSee(__('Company Name'))
            ->assertSee(__('Subclient Name'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_communication_histories(): void
    {
        $eLetter = ELetter::factory()
            ->hasAttached($this->consumer)
            ->create();

        Livewire::test(ELetterHistories::class, ['consumer' => $this->consumer])
            ->assertViewIs('livewire.creditor.manage-consumers.consumer-profile.e-letter-histories')
            ->assertViewHas('eLetters', fn (LengthAwarePaginator $eLetters) => $eLetter->consumerELetters->first()->is($eLetters->getCollection()->first()))
            ->assertSee($eLetter->consumers->first()->pivot->read_by_consumer ? 'Read' : 'Unread')
            ->assertDontSee(__('No result found'));
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

        Livewire::test(ELetterHistories::class, ['consumer' => $this->consumer])
            ->call('downloadCFPBLetter', $consumerELetter)
            ->assertOk()
            ->assertFileDownloaded();

        Notification::assertNotified(__('CFPB Collection letter downloaded.'));
    }
}
