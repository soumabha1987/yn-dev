<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomationCampaign;

use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\ListPage;
use App\Models\AutomatedTemplate;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    protected User $user;

    protected AutomatedTemplate $automatedTemplate;

    protected CommunicationStatus $communicationStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->automatedTemplate = AutomatedTemplate::factory()->for($this->user)->create();

        $this->communicationStatus = CommunicationStatus::factory()
            ->for($this->automatedTemplate, 'emailTemplate')
            ->for($this->automatedTemplate, 'smsTemplate')
            ->create(['trigger_type' => CommunicationStatusTriggerType::SCHEDULED]);
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('super-admin.automation-campaigns'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automation-campaign.list-page')
            ->assertViewHas('automationCampaigns', fn (LengthAwarePaginator $automationCampaigns) => $automationCampaigns->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
                'enabled' => true,
            ]);

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automation-campaign.list-page')
            ->assertViewHas('automationCampaigns', fn (LengthAwarePaginator $automationCampaigns) => $automationCampaign->is($automationCampaigns->getCollection()->first()))
            ->assertSee($automationCampaign->communicationStatus->code)
            ->assertSee(Str::words($automationCampaign->communicationStatus->description, 3))
            ->assertSee($automationCampaign->enabled ? __('Pause') : __('Resume'))
            ->assertOk();
    }

    #[Test]
    public function toggle_enabled(): void
    {
        $method = ($enabled = fake()->boolean()) ? 'assertFalse' : 'assertTrue';

        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
                'enabled' => $enabled,
            ]);

        Livewire::test(ListPage::class)
            ->call('updateEnabled', $automationCampaign)
            ->assertOk();

        $this->$method($automationCampaign->refresh()->enabled);
    }

    #[Test]
    public function it_can_delete_automation_campaign(): void
    {
        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::test(ListPage::class)
            ->call('delete', $automationCampaign)
            ->assertOk();

        $this->assertModelMissing($automationCampaign);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        $communicationStatuses = CommunicationStatus::factory(3)
            ->sequence(
                ['code' => CommunicationCode::CREDITOR_REMOVED_ACCOUNT],
                ['code' => CommunicationCode::NEW_ACCOUNT],
                ['code' => CommunicationCode::PAYMENT_FAILED_WHEN_PIF]
            )
            ->create([
                'automated_email_template_id' => $this->automatedTemplate->id,
                'automated_sms_template_id' => $this->automatedTemplate->id,
                'trigger_type' => CommunicationStatusTriggerType::SCHEDULED,
            ]);

        $createdAutomationCampaigns = AutomationCampaign::factory(3)
            ->sequence(fn (Sequence $sequence) => ['communication_status_id' => $communicationStatuses->get($sequence->index)->id])
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'status')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automationCampaigns',
                fn (LengthAwarePaginator $automationCampaigns) => $direction === 'ASC'
                    ? $createdAutomationCampaigns->first()->is($automationCampaigns->getCollection()->first())
                    : $createdAutomationCampaigns->last()->is($automationCampaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_frequency(string $direction): void
    {
        $createdAutomationCampaigns = AutomationCampaign::factory(5)
            ->for($this->communicationStatus)
            ->sequence(
                ['frequency' => AutomationCampaignFrequency::DAILY],
                ['frequency' => AutomationCampaignFrequency::HOURLY],
                ['frequency' => AutomationCampaignFrequency::MONTHLY],
                ['frequency' => AutomationCampaignFrequency::ONCE],
                ['frequency' => AutomationCampaignFrequency::WEEKLY],
            )
            ->create([
                'hourly' => 12,
                'weekly' => 2,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::withQueryParams([
            'sort' => 'frequency',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'frequency')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automationCampaigns',
                fn (LengthAwarePaginator $automationCampaigns) => $direction === 'ASC'
                    ? $createdAutomationCampaigns->first()->is($automationCampaigns->getCollection()->first())
                    : $createdAutomationCampaigns->last()->is($automationCampaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_email_template_name(string $direction): void
    {
        $createdAutomationCampaigns = AutomationCampaign::factory(6)
            ->sequence(fn (Sequence $sequence) => [
                'communication_status_id' => CommunicationStatus::factory()
                    ->for(AutomatedTemplate::factory()->email()->state(['name' => range('A', 'Z')[$sequence->index]]), 'emailTemplate')
                    ->for($this->automatedTemplate, 'smsTemplate'),
            ])
            ->create([
                'frequency' => AutomationCampaignFrequency::WEEKLY,
                'weekly' => 2,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::withQueryParams([
            'sort' => 'email_template_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'email_template_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automationCampaigns',
                fn (LengthAwarePaginator $automationCampaigns) => $direction === 'ASC'
                    ? $createdAutomationCampaigns->first()->is($automationCampaigns->getCollection()->first())
                    : $createdAutomationCampaigns->last()->is($automationCampaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sms_template_name(string $direction): void
    {
        $createdAutomationCampaigns = AutomationCampaign::factory(6)
            ->sequence(fn (Sequence $sequence) => [
                'communication_status_id' => CommunicationStatus::factory()
                    ->for($this->automatedTemplate, 'emailTemplate')
                    ->for(AutomatedTemplate::factory()->sms()->state(['name' => range('A', 'Z')[$sequence->index]]), 'smsTemplate'),
            ])
            ->create([
                'frequency' => AutomationCampaignFrequency::WEEKLY,
                'weekly' => 2,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::withQueryParams([
            'sort' => 'sms_template_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sms_template_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automationCampaigns',
                fn (LengthAwarePaginator $automationCampaigns) => $direction === 'ASC'
                    ? $createdAutomationCampaigns->first()->is($automationCampaigns->getCollection()->first())
                    : $createdAutomationCampaigns->last()->is($automationCampaigns->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_enabled(string $direction): void
    {
        $createdAutomationCampaigns = AutomationCampaign::factory(2)
            ->sequence(
                ['enabled' => false],
                ['enabled' => true],
            )
            ->create([
                'frequency' => AutomationCampaignFrequency::WEEKLY,
                'weekly' => 2,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::withQueryParams([
            'sort' => 'enabled',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'enabled')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automationCampaigns',
                fn (LengthAwarePaginator $automationCampaigns) => $direction === 'ASC'
                    ? $createdAutomationCampaigns->first()->is($automationCampaigns->getCollection()->first())
                    : $createdAutomationCampaigns->last()->is($automationCampaigns->getCollection()->first())
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
