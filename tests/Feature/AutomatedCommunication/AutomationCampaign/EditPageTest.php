<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomationCampaign;

use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\EditPage;
use App\Models\AutomatedTemplate;
use App\Models\AutomationCampaign;
use App\Models\CommunicationStatus;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditPageTest extends TestCase
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
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('super-admin.automation-campaigns.edit', ['automationCampaign' => $automationCampaign->id]))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_view(): void
    {
        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::test(EditPage::class, ['automationCampaign' => $automationCampaign])
            ->assertViewIs('livewire.creditor.automated-communication.automation-campaign.edit-page')
            ->assertSet('form.communication_status_id', $this->communicationStatus->id)
            ->assertSet('form.frequency', $automationCampaign->frequency->value)
            ->assertSet('form.hourly', 12)
            ->assertSet('form.hours', $automationCampaign->start_at->format('g'))
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation(): void
    {
        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::test(EditPage::class, ['automationCampaign' => $automationCampaign])
            ->set('form.communication_status_id', 59)
            ->set('form.frequency', '')
            ->set('form.hours', 34)
            ->call('update')
            ->assertHasErrors([
                'form.communication_status_id' => ['exists'],
                'form.frequency' => ['required'],
                'form.hours' => ['max'],
            ])
            ->assertHasNoErrors(['form.hourly', 'form.weekly'])
            ->assertOk();
    }

    #[Test]
    public function update_automation_campaign(): void
    {
        $automationCampaign = AutomationCampaign::factory()
            ->for($this->communicationStatus)
            ->create([
                'frequency' => AutomationCampaignFrequency::HOURLY->value,
                'hourly' => 12,
                'start_at' => now()->startOfDay()->addHours(12)->toDateTimeString(),
            ]);

        Livewire::test(EditPage::class, ['automationCampaign' => $automationCampaign])
            ->set('form.frequency', AutomationCampaignFrequency::WEEKLY)
            ->set('form.weekly', Carbon::MONDAY)
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.automation-campaigns'));

        Notification::assertNotified(__('Schedule alerts updated.'));

        $this->assertEquals(AutomationCampaignFrequency::WEEKLY, $automationCampaign->refresh()->frequency);
        $this->assertEquals(1, $automationCampaign->weekly);
    }
}
