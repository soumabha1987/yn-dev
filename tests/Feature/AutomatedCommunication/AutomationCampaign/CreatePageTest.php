<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomationCampaign;

use App\Enums\AutomationCampaignFrequency;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\CreatePage;
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

class CreatePageTest extends TestCase
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
    public function it_can_render_livewire_component_when_visit_the_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('super-admin.automation-campaigns.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_correct_component_with_defined_view(): void
    {
        Livewire::test(CreatePage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automation-campaign.create-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_redirect_to_the_component_when_communication_status_are_not_available(): void
    {
        $this->communicationStatus->update(['automated_email_template_id' => null]);

        Livewire::test(CreatePage::class)
            ->assertRedirect(route('super-admin.automation-campaigns'));

        Notification::assertNotified(__('Campaign with statuses already exists.'));
    }

    #[Test]
    public function it_can_create_throw_required_validation(): void
    {
        Livewire::test(CreatePage::class)
            ->call('create')
            ->assertHasErrors([
                'form.communication_status_id' => ['required'],
                'form.frequency' => ['required'],
                'form.hours' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function not_allow_the_communication_status_which_is_not_have_trigger_type_schedule_or_both(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.communication_status_id', fake()->numberBetween(100, 200))
            ->call('create')
            ->assertHasErrors([
                'form.communication_status_id' => ['exists'],
                'form.frequency' => ['required'],
                'form.hours' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function if_frequency_is_hourly_then_hourly_is_required(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.communication_status_id', $this->communicationStatus->id)
            ->set('form.frequency', AutomationCampaignFrequency::HOURLY)
            ->set('form.hours', fake()->numberBetween(2, 23))
            ->call('create')
            ->assertHasErrors(['form.hourly' => ['required']])
            ->assertHasNoErrors(['form.communication_status_id', 'form.frequency', 'form.hours'])
            ->assertOk();
    }

    #[Test]
    public function if_frequency_is_weekly_then_weekly_field_is_required(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.communication_status_id', $this->communicationStatus->id)
            ->set('form.frequency', AutomationCampaignFrequency::WEEKLY)
            ->set('form.hours', fake()->numberBetween(2, 23))
            ->call('create')
            ->assertHasErrors(['form.weekly' => ['required']])
            ->assertHasNoErrors(['form.communication_status_id', 'form.frequency', 'form.hours', 'form.hourly'])
            ->assertOk();
    }

    #[Test]
    public function it_can_create_the_hourly_automation_campaign(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.communication_status_id', $this->communicationStatus->id)
            ->set('form.frequency', AutomationCampaignFrequency::HOURLY)
            ->set('form.hours', $hours = fake()->numberBetween(2, 23))
            ->set('form.hourly', 12)
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(AutomationCampaign::class, [
            'communication_status_id' => $this->communicationStatus->id,
            'frequency' => AutomationCampaignFrequency::HOURLY->value,
            'start_at' => now()->toDateString() . ' ' . sprintf('%02d', $hours) . ':00:00',
            'hourly' => 12,
            'weekly' => null,
        ]);
    }

    #[Test]
    public function it_can_create_weekly_automation_campaign(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.communication_status_id', $this->communicationStatus->id)
            ->set('form.frequency', AutomationCampaignFrequency::WEEKLY)
            ->set('form.hours', $hours = fake()->numberBetween(2, 23))
            ->set('form.weekly', Carbon::MONDAY)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.automation-campaigns'));

        Notification::assertNotified(__('Schedule is set up!'));

        $this->assertDatabaseHas(AutomationCampaign::class, [
            'communication_status_id' => $this->communicationStatus->id,
            'frequency' => AutomationCampaignFrequency::WEEKLY->value,
            'start_at' => now()->startOfDay()->addHours($hours)->toDateTimeString(),
            'weekly' => Carbon::MONDAY,
            'hourly' => null,
        ]);
    }
}
