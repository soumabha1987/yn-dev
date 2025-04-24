<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\CampaignTracker;

use App\Enums\CampaignFrequency;
use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Communications\CampaignTracker\ListPage;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\CampaignTrackerConsumer;
use App\Models\Group;
use App\Models\Template;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $this->user->update(['subclient_id' => null]);

        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        $this->get(route('creditor.communication.campaign-trackers'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_livewire_campaign_tracker_list_page(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.communications.campaign-tracker.list-page')
            ->assertViewHas('campaignTrackers', fn (LengthAwarePaginator $campaignTrackers) => $campaignTrackers->isEmpty())
            ->assertSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_can_view_campaign_tracker_with_data(): void
    {
        $campaignTracker = CampaignTracker::factory()
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $campaignTrackers->getCollection()->contains($campaignTracker)
            )
            ->assertDontSee(__('No result found'))
            ->assertSee($campaignTracker->created_at->formatWithTimezone())
            ->assertSee($campaignTracker->campaign->template->name ?? 'N/A')
            ->assertSee($campaignTracker->campaign->group->name ?? 'N/A')
            ->assertSee(Number::currency((float) $campaignTracker->total_balance_of_consumers ?? 0))
            ->assertSee(Number::format($campaignTracker->consumer_count))
            ->assertSee(Number::format($campaignTracker->delivered_count))
            ->assertSee(Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->delivered_count * 100 / $campaignTracker->consumer_count) : 0, 2))
            ->assertSee(Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->clicks_count * 100 / $campaignTracker->consumer_count) : 0, 2))
            ->assertSee(Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->pif_completed_count * 100 / $campaignTracker->consumer_count) : 0, 2))
            ->assertSee(Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->ppl_completed_count * 100 / $campaignTracker->consumer_count) : 0, 2))
            ->assertSee(Number::percentage($campaignTracker->consumer_count > 0 ? ($campaignTracker->clicks_count * 100 / $campaignTracker->consumer_count) : 0, 2))
            ->assertOk();
    }

    #[Test]
    public function it_can_call_campaign_tracker_re_run(): void
    {
        $campaignTracker = CampaignTracker::factory()
            ->for($campaign = Campaign::factory()->create([
                'company_id' => $this->user->company_id,
                'frequency' => CampaignFrequency::ONCE,
                'day_of_week' => null,
                'day_of_month' => null,
            ]))
            ->create();

        $this->assertDatabaseCount(Campaign::class, 1);

        Livewire::test(ListPage::class)
            ->call('reRun', $campaignTracker->id)
            ->assertOk();

        Notification::assertNotified(__('Your new campaign has been created!'));

        $this->assertDatabaseCount(Campaign::class, 2);

        $startDate = now('UTC') < now('UTC')->setTime(16, 30) ? today() : today()->addDay();

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $campaign->user_id,
            'company_id' => $campaign->company_id,
            'template_id' => $campaign->template_id,
            'group_id' => $campaign->group_id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => $startDate->toDateString(),
            'day_of_week' => null,
            'day_of_month' => null,
            'end_date' => null,
        ]);

        $this->assertNotEquals($startDate->toDateString(), $campaign->start_date);
    }

    #[Test]
    public function it_can_call_campaign_tracker_re_run_with_different_frequency(): void
    {
        $campaignTracker = CampaignTracker::factory()
            ->for($campaign = Campaign::factory()->create([
                'company_id' => $this->user->company_id,
                'frequency' => fake()->randomElement([CampaignFrequency::DAILY, CampaignFrequency::WEEKLY, CampaignFrequency::MONTHLY]),
                'day_of_week' => null,
                'day_of_month' => null,
            ]))
            ->create();

        $this->assertDatabaseCount(Campaign::class, 1);

        Livewire::test(ListPage::class)
            ->call('reRun', $campaignTracker->id)
            ->assertOk();

        Notification::assertNotified(__('Your communication matches an existing campaign.'));

        $this->assertDatabaseCount(Campaign::class, 1);

        $startDate = now('UTC') < now('UTC')->setTime(16, 30) ? today() : today()->addDay();

        $this->assertDatabaseMissing(Campaign::class, [
            'user_id' => $campaign->user_id,
            'company_id' => $campaign->company_id,
            'template_id' => $campaign->template_id,
            'group_id' => $campaign->group_id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => $startDate->toDateString(),
            'day_of_week' => null,
            'day_of_month' => null,
            'end_date' => null,
        ]);
    }

    #[Test]
    public function it_can_call_export_consumers(): void
    {
        Storage::fake();

        $campaignTracker = CampaignTracker::factory()->create();

        CampaignTrackerConsumer::factory(10)->create(['campaign_tracker_id' => $campaignTracker->id]);

        Livewire::test(ListPage::class)
            ->call('exportConsumers', $campaignTracker->id)
            ->assertOk()
            ->assertFileDownloaded();

        Notification::assertNotified(__('Consumers exported!'));
    }

    #[Test]
    public function it_can_call_export_consumers_but_campaign_have_no_consumers(): void
    {
        Storage::fake();

        $campaignTracker = CampaignTracker::factory()->create();

        Livewire::test(ListPage::class)
            ->call('exportConsumers', $campaignTracker->id)
            ->assertOk()
            ->assertNoFileDownloaded();

        Notification::assertNotified(__('No consumers found. Please email help@younegotiate.com if this is an error.'));

        Notification::assertNotNotified(__('Consumers exported!'));
    }

    #[Test]
    #[DataProvider('templateOrGroupDeleted')]
    public function it_can_call_campaign_tracker_re_run_with_campaign_template_and_group_deleted(string $templateOrGroup): void
    {
        $template = Template::factory()->create(['deleted_at' => now()]);

        $group = Group::factory()->create(['deleted_at' => now()]);

        $campaignTracker = CampaignTracker::factory()
            ->for($campaign = Campaign::factory()
                ->for($templateOrGroup === 'group' ? $group : $template)
                ->create([
                    'company_id' => $this->user->company_id,
                    'frequency' => CampaignFrequency::ONCE,
                    'day_of_week' => null,
                    'day_of_month' => null,
                ]))
            ->create();

        $this->assertDatabaseCount(Campaign::class, 1);

        Livewire::test(ListPage::class)
            ->call('reRun', $campaignTracker->id)
            ->assertOk();

        Notification::assertNotified(__('This campaign no longer exists, please create new campaign.'));

        Notification::assertNotNotified(__('Your communication matches an existing campaign.'));

        $this->assertDatabaseCount(Campaign::class, 1);

        $startDate = now('UTC') < now('UTC')->setTime(16, 30) ? today() : today()->addDay();

        $this->assertDatabaseMissing(Campaign::class, [
            'user_id' => $campaign->user_id,
            'company_id' => $campaign->company_id,
            'template_id' => $campaign->template_id,
            'group_id' => $campaign->group_id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => $startDate->toDateString(),
            'day_of_week' => null,
            'day_of_month' => null,
            'end_date' => null,
        ]);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_sent_on(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(10)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => ['created_at' => today()->subDays($sequence->index + 2)])
            ->create();

        Livewire::withQueryParams(['sort' => 'sent-on', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sent-on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_template_name(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'campaign_id' => Campaign::factory()
                    ->for(Template::factory()->create(['name' => range('A', 'Z')[$sequence->index + 2]]))
                    ->state(['company_id' => $this->user->company_id]),
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'template-name', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'template-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_group_name(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'campaign_id' => Campaign::factory()
                    ->for(Group::factory()->create(['name' => range('A', 'Z')[$sequence->index + 2]]))
                    ->state(['company_id' => $this->user->company_id]),
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'group-name', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'group-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_opened(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(15)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'clicks_count' => $sequence->index * 100,
                'consumer_count' => 100,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'opened', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'opened')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_consumer_count(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'consumer_count' => $sequence->index + 10,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'sent', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sent')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_delivered_count(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'delivered_count' => $sequence->index + 10,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'delivered', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'delivered')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_delivered_percentage(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'delivered_count' => $sequence->index * 100,
                'consumer_count' => 100,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'delivered-percentage', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'delivered-percentage')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_pif_offer(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'pif_completed_count' => $sequence->index * 100,
                'consumer_count' => 100,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'pif-offer', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'pif-offer')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_ppl_offer(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'ppl_completed_count' => $sequence->index * 100,
                'consumer_count' => 100,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'ppl-offer', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'ppl-offer')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_custom_offer_count(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(5)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => [
                'custom_offer_count' => $sequence->index * 100,
                'consumer_count' => 100,
            ])
            ->create();

        Livewire::withQueryParams(['sort' => 'sent-offer', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sent-offer')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_total_balance_of_consumers(string $direction): void
    {
        $createdCampaignTrackers = CampaignTracker::factory(15)
            ->for(Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->sequence(fn (Sequence $sequence) => ['total_balance_of_consumers' => $sequence->index * 100])
            ->create();

        Livewire::withQueryParams(['sort' => 'total-balance', 'direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'total-balance')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaignTrackers',
                fn (LengthAwarePaginator $campaignTrackers) => $direction === 'ASC'
                    ? $createdCampaignTrackers->first()->is($campaignTrackers->getCollection()->first())
                    : $createdCampaignTrackers->last()->is($campaignTrackers->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }

    public static function templateOrGroupDeleted(): array
    {
        return [
            ['group'],
            ['template'],
        ];
    }
}
