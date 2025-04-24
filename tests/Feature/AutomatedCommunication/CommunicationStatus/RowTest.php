<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\CommunicationStatus;

use App\Console\Commands\CommunicationStatusCommand;
use App\Enums\AutomatedTemplateType;
use App\Livewire\Creditor\AutomatedCommunication\CommunicationStatus\Row;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\TestCase;

class RowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call(CommunicationStatusCommand::class);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.row')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_view_and_see_the_records(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        $emailTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::EMAIL,
        ]);

        $smsTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::SMS,
        ]);

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.row')
            ->assertViewHas('smsTemplateDropdownList', fn (Collection $smsTemplates) => $smsTemplates->first() === $smsTemplate->name)
            ->assertViewHas('emailTemplateDropdownList', fn (Collection $emailTemplates) => $emailTemplates->first() === $emailTemplate->name)
            ->assertSee(str($communicationStatus->description)->words(3)->toString())
            ->assertSee($communicationStatus->trigger_type->name)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_with_data(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        $emailTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::EMAIL,
        ]);

        $smsTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::SMS,
        ]);

        $communicationStatus->automated_email_template_id = $emailTemplate->id;
        $communicationStatus->automated_sms_template_id = $smsTemplate->id;
        $communicationStatus->save();

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->assertSet('automated_email_template_id', $emailTemplate->id)
            ->assertSet('automated_sms_template_id', $smsTemplate->id)
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.row')
            ->assertViewHas('smsTemplateDropdownList', fn (Collection $smsTemplates) => $smsTemplates->first() === $smsTemplate->name)
            ->assertViewHas('emailTemplateDropdownList', fn (Collection $emailTemplates) => $emailTemplates->first() === $emailTemplate->name)
            ->assertSee(str($communicationStatus->description)->words(3)->toString())
            ->assertSee($communicationStatus->trigger_type->name)
            ->assertOk();
    }

    #[Test]
    public function it_can_give_required_validation(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_email_template_id', null)
            ->assertHasErrors([
                'automated_email_template_id' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_give_sms_template_required_validation(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_sms_template_id', null)
            ->assertHasErrors([
                'automated_sms_template_id' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_give_exists_validation(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        $emailTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::EMAIL,
        ]);

        $smsTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::SMS,
        ]);

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_email_template_id', $smsTemplate->id)
            ->assertHasErrors([
                'automated_email_template_id' => ['exists'],
            ])
            ->assertOk();

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_sms_template_id', $emailTemplate->id)
            ->assertHasErrors([
                'automated_sms_template_id' => ['exists'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_communication_status_with_email_template(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        $emailTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::EMAIL,
        ]);

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_email_template_id', $emailTemplate->id)
            ->assertHasNoErrors()
            ->assertDispatched('refresh-email')
            ->assertOk();

        $this->assertEquals($communicationStatus->refresh()->automated_email_template_id, $emailTemplate->id);
        Notification::assertNotified(__('Email template updated successfully!'));
    }

    #[Test]
    public function it_can_update_communication_status_with_sms_template(): void
    {
        $communicationStatus = CommunicationStatus::first();
        $loop = new stdClass;
        $loop->last = false;

        $smsTemplate = AutomatedTemplate::query()->create([
            'name' => fake()->name(),
            'type' => AutomatedTemplateType::SMS,
        ]);

        Livewire::test(Row::class, ['communicationStatus' => $communicationStatus, 'loop' => $loop])
            ->set('automated_sms_template_id', $smsTemplate->id)
            ->assertHasNoErrors()
            ->assertDispatched('refresh-sms')
            ->assertOk();

        $this->assertEquals($communicationStatus->refresh()->automated_sms_template_id, $smsTemplate->id);
        Notification::assertNotified(__('SMS template updated successfully!'));
    }
}
