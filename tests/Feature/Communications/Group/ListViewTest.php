<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\Group;

use App\Enums\ConsumerStatus;
use App\Enums\GroupConsumerState;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\Communications\Group\ListView;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Group;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListViewTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->user->assignRole(Role::query()->create(['name' => EnumsRole::CREDITOR]));

        $this->withoutVite()
            ->actingAs($this->user);
    }

    #[Test]
    public function it_renders_the_component_with_view_page(): void
    {
        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertViewHas('groups', fn (LengthAwarePaginator $groups) => $groups->isEmpty())
            ->assertSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_can_renders_component_with_data(): void
    {
        $createdGroups = Group::factory()
            ->forEachSequence(
                ['company_id' => $this->user->company_id],
                ['company_id' => Company::factory()],
            )
            ->create();

        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertViewHas('groups', function (LengthAwarePaginator $groups) use ($createdGroups): bool {
                return $groups->count() === 1
                    && $groups->getCollection()->contains($createdGroups->first())
                    && $groups->getCollection()->doesntContain($createdGroups->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_renders_the_component_with_campaign_with_view_data(): void
    {
        $group = Group::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertViewHas('groups', fn (LengthAwarePaginator $groups) => $groups->getCollection()->contains($group))
            ->assertSee($group->name)
            ->assertSee($group->created_at->formatWithTimezone())
            ->assertSeeHtml('x-on:refresh-parent.window="$wire.$refresh"');
    }

    #[Test]
    public function it_can_delete_the_group(): void
    {
        $group = Group::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->call('delete', $group->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Successfully deleted.'));

        $this->assertSoftDeleted($group);
    }

    #[Test]
    public function it_can_export_the_consumer_of_that_group(): void
    {
        Consumer::factory(3)->create([
            'status' => ConsumerStatus::JOINED,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'consumer_profile_id' => null,
        ]);

        $group = Group::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'consumer_state' => GroupConsumerState::VIEWED_OFFER_BUT_NO_RESPONSE,
        ]);

        Livewire::test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->call('export', $group->id)
            ->assertOk()
            ->assertFileDownloaded();
    }

    #[Test]
    public function it_can_calculate_the_group_size(): void
    {
        Consumer::factory(3)->create([
            'status' => ConsumerStatus::VISITED,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'consumer_profile_id' => null,
            'current_balance' => 23,
        ]);

        $group = Group::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'consumer_state' => GroupConsumerState::NOT_VIEWED_OFFER,
        ]);

        Livewire::test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertSet('openModal', false)
            ->call('calculateGroupSize', $group->id)
            ->assertOk()
            ->assertSet('groupSize', 3)
            ->assertSet('totalBalance', Number::currency(69))
            ->assertSet('openModal', true)
            ->assertDispatched('close-menu');
    }

    #[Test]
    public function it_can_search_by_group_name(): void
    {
        [$firstGroup] = Group::factory()
            ->forEachSequence(
                ['name' => 'Test group 1'],
                ['name' => 'Test group 2']
            )
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'consumer_state' => GroupConsumerState::NOT_VIEWED_OFFER,
            ]);

        Livewire::withQueryParams(['search' => 'Test group 1'])
            ->test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertSet('openModal', false)
            ->assertViewHas('groups', fn (LengthAwarePaginator $groups): bool => $groups->getCollection()->first()->is($firstGroup) && $groups->total() === 1);
    }

    #[Test]
    public function it_can_search_by_consumer_state(): void
    {
        $groups = Group::factory()
            ->forEachSequence(
                ['name' => 'Test group 1', 'consumer_state' => GroupConsumerState::ALL_ACTIVE->value],
                ['name' => 'Test group 2', 'consumer_state' => GroupConsumerState::VIEWED_OFFER_BUT_NO_RESPONSE->value]
            )
            ->create([
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams(['search' => 'Viewed offer (no response)'])
            ->test(ListView::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.communications.group.list-view')
            ->assertSet('openModal', false)
            ->assertViewHas('groups', fn (LengthAwarePaginator $groups): bool => $groups->getCollection()->first()->is($groups->last()) && $groups->total() === 1);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_created_at(string $direction): void
    {
        $createdGroups = Group::factory(5)
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->addDays($sequence->index + 1)])
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'created-on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'groups',
                fn (LengthAwarePaginator $groups) => $direction === 'ASC'
                    ? $createdGroups->first()->is($groups->getCollection()->first())
                    : $createdGroups->last()->is($groups->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_name(string $direction): void
    {
        $createdGroups = Group::factory(5)
            ->sequence(fn (Sequence $sequence) => ['name' => range('A', 'Z')[$sequence->index]])
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams(['sort' => 'name', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'groups',
                fn (LengthAwarePaginator $groups) => $direction === 'ASC'
                    ? $createdGroups->first()->is($groups->getCollection()->first())
                    : $createdGroups->last()->is($groups->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_pay_terms(string $direction): void
    {
        $createdGroups = Group::factory()
            ->forEachSequence(
                ['pif_balance_discount_percent' => 10, 'ppa_balance_discount_percent' => 10, 'min_monthly_pay_percent' => 10, 'max_days_first_pay' => 10, 'minimum_settlement_percentage' => 10, 'minimum_payment_plan_percentage' => 10, 'max_first_pay_days' => 100],
                ['pif_balance_discount_percent' => null, 'ppa_balance_discount_percent' => null, 'min_monthly_pay_percent' => null, 'max_days_first_pay' => null, 'minimum_settlement_percentage' => null, 'minimum_payment_plan_percentage' => null, 'max_first_pay_days' => null],
            )
            ->create([
                'user_id' => $this->user->id,
                'company_id' => $this->user->company_id,
            ]);

        Livewire::withQueryParams(['sort' => 'pay-terms', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'pay-terms')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'groups',
                fn (LengthAwarePaginator $groups) => $direction === 'ASC'
                    ? $createdGroups->last()->is($groups->getCollection()->first())
                    : $createdGroups->first()->is($groups->getCollection()->first())
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
