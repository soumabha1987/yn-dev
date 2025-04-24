<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\Campaign;

use App\Enums\CampaignFrequency;
use App\Enums\Role as EnumRole;
use App\Enums\TemplateType;
use App\Livewire\Creditor\Communications\Campaign\ListView;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\CampaignTrackerConsumer;
use App\Models\Company;
use App\Models\Group;
use App\Models\Template;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListViewTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);
    }

    #[Test]
    public function it_renders_the_component_with_view_page(): void
    {
        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', fn (LengthAwarePaginator $campaigns) => $campaigns->isEmpty())
            ->assertSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_renders_the_component_with_view_page_where_role_super_admin(): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        Livewire::actingAs($user)
            ->test(ListView::class)
            ->assertSet('isCreditor', false)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', fn (LengthAwarePaginator $campaigns) => $campaigns->isEmpty())
            ->assertSee(__('No result found'))
            ->assertDontSee(__('eLetter Name'))
            ->assertSee(__('Template Name'))
            ->assertSee(__('Type'))
            ->assertOk();
    }

    #[Test]
    public function it_renders_the_component_with_other_company_campaign_data(): void
    {
        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                ['company_id' => $this->user->company_id],
                ['company_id' => Company::factory()],
            )
            ->create();

        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', function (LengthAwarePaginator $campaigns) use ($createdCampaigns): bool {
                return $campaigns->getCollection()->contains($createdCampaigns->first())
                    && $campaigns->getCollection()->doesntContain($createdCampaigns->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_renders_the_component_with_campaign_with_view_data(): void
    {
        $campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]);

        $frequency = (string) str($campaign->frequency->name)->title();

        $executionTime = match ($campaign->frequency) {
            CampaignFrequency::MONTHLY => __("{$frequency} on the :monthDate", ['monthDate' => now()->day($campaign->day_of_month)->format('jS')]),
            CampaignFrequency::WEEKLY => __("{$frequency} on :weekDay", ['weekDay' => now()->startOfWeek()->addDays($campaign->day_of_week - 1)->format('l')]),
            CampaignFrequency::DAILY => __("{$frequency}"),
            CampaignFrequency::ONCE => __('One time on :date', ['date' => $campaign->start_date->format('M d, Y')]),
        };

        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', fn (LengthAwarePaginator $campaigns) => $campaigns->getCollection()->contains($campaign))
            ->assertSee($campaign->start_date->format('M d, Y'))
            ->assertSee($campaign->end_date?->format('M d, Y') ?? '-')
            ->assertSee($campaign->template->name)
            ->assertSee($campaign->group->name)
            ->assertSee($campaign->frequency->displayName())
            ->assertSee($executionTime)
            ->assertOk();
    }

    #[Test]
    public function it_can_call_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(ListView::class)
            ->call('delete', $campaign->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Campaign deleted.'));

        $this->assertModelMissing($campaign);
    }

    #[Test]
    public function it_can_call_delete_campaign_with_multiple_campaign_tracker(): void
    {

        CampaignTracker::factory()
            ->for($campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]))
            ->create();

        Livewire::test(ListView::class)
            ->call('delete', $campaign->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Campaign deleted.'));

        $this->assertModelMissing($campaign);
    }

    #[Test]
    public function it_can_call_delete_campaign_with_multiple_campaign_tracker_consumer(): void
    {
        $campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]);

        CampaignTrackerConsumer::factory(10)
            ->for(CampaignTracker::factory()->for($campaign)->create())
            ->create();

        Livewire::test(ListView::class)
            ->call('delete', $campaign->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Campaign deleted.'));

        $this->assertModelMissing($campaign);
    }

    #[Test]
    public function it_can_not_delete_other_company_campaign(): void
    {
        $campaign = Campaign::factory()->for(Company::factory())->create();

        Livewire::test(ListView::class)
            ->call('delete', $campaign->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Sorry you can not delete this campaign.'));

        Notification::assertNotNotified(__('Campaign deleted.'));

        $this->assertModelExists($campaign);
    }

    #[Test]
    public function it_can_search_by_frequency(): void
    {
        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                ['frequency' => CampaignFrequency::MONTHLY],
                ['frequency' => CampaignFrequency::WEEKLY],
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['search' => CampaignFrequency::MONTHLY->value])
            ->test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', function (LengthAwarePaginator $campaigns) use ($createdCampaigns) {
                return $campaigns->getCollection()->contains($createdCampaigns->first())
                    && $campaigns->getCollection()->doesntContain($createdCampaigns->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_search_by_template_name(): void
    {
        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                ['template_id' => Template::factory()->state(['name' => $templateName = fake()->name()])],
                ['template_id' => Template::factory()->state(['name' => fake()->name()])],
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['search' => $templateName])
            ->test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', function (LengthAwarePaginator $campaigns) use ($createdCampaigns) {
                return $campaigns->getCollection()->contains($createdCampaigns->first())
                    && $campaigns->getCollection()->doesntContain($createdCampaigns->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_search_by_group_name(): void
    {
        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                ['group_id' => Group::factory()->state(['name' => $groupName = fake()->name()])],
                ['group_id' => Group::factory()->state(['name' => fake()->name()])],
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['search' => $groupName])
            ->test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.campaign.list-view')
            ->assertViewHas('campaigns', function (LengthAwarePaginator $campaigns) use ($createdCampaigns) {
                return $campaigns->getCollection()->contains($createdCampaigns->first())
                    && $campaigns->getCollection()->doesntContain($createdCampaigns->last());
            })
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_start_date(string $direction): void
    {
        $createdCampaigns = Campaign::factory(5)
            ->sequence(fn (Sequence $sequence) => ['start_date' => today()->addDays($sequence->index + 1)])
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['sort' => 'start-date', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'start-date')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_end_date(string $direction): void
    {
        $createdCampaigns = Campaign::factory(5)
            ->sequence(fn (Sequence $sequence) => ['end_date' => today()->addDays($sequence->index + 1)])
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['sort' => 'end-date', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'end-date')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_template_name(string $direction): void
    {
        $createdCampaigns = Campaign::factory(5)
            ->sequence(fn (Sequence $sequence) => ['template_id' => Template::factory()
                ->state([
                    'name' => range('A', 'Z')[$sequence->index],
                    'type' => TemplateType::E_LETTER,
                    'company_id' => $this->user->company_id,
                ]),
            ])
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['sort' => 'template-name', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'template-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_template_type_when_super_admin_role(string $direction): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                [
                    'template_id' => Template::factory()
                        ->state([
                            'type' => TemplateType::EMAIL,
                            'company_id' => $user->company_id,
                        ]),
                ],
                [
                    'template_id' => Template::factory()
                        ->state([
                            'type' => TemplateType::SMS,
                            'company_id' => $user->company_id,
                        ]),
                ],
            )
            ->create(['company_id' => $user->company_id]);

        Livewire::withQueryParams(['sort' => 'template-type', 'direction' => $direction === 'ASC'])
            ->actingAs($user)
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'template-type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_group_name(string $direction): void
    {
        $createdCampaigns = Campaign::factory(5)
            ->sequence(fn (Sequence $sequence) => ['group_id' => Group::factory()
                ->state([
                    'name' => range('A', 'Z')[$sequence->index],
                    'company_id' => $this->user->company_id,
                ]),
            ])
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['sort' => 'group-name', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'group-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_frequency(string $direction): void
    {
        $createdCampaigns = Campaign::factory()
            ->forEachSequence(
                ['frequency' => CampaignFrequency::DAILY],
                ['frequency' => CampaignFrequency::MONTHLY],
                ['frequency' => CampaignFrequency::ONCE],
                ['frequency' => CampaignFrequency::WEEKLY],
            )
            ->create(['company_id' => $this->user->company_id]);

        Livewire::withQueryParams(['sort' => 'frequency', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'frequency')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'campaigns',
                fn (LengthAwarePaginator $campaigns) => $direction === 'ASC'
                    ? $createdCampaigns->first()->is($campaigns->getCollection()->first())
                    : $createdCampaigns->last()->is($campaigns->getCollection()->first())
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
