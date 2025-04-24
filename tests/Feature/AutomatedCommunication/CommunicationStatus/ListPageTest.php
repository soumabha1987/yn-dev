<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\CommunicationStatus;

use App\Console\Commands\CommunicationStatusCommand;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\CommunicationStatus\ListPage;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call(CommunicationStatusCommand::class);
    }

    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user = User::factory()->create();

        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.configure-communication-status'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.list-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_communication_statuses(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.list-page')
            ->assertViewHas('communicationStatuses', fn (Collection $communicationStatuses): bool => $communicationStatuses->first()->code === CommunicationCode::WELCOME)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_communication_status_according_code(): void
    {
        Livewire::withQueryParams(['search' => CommunicationCode::WELCOME->value])
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.communication-status.list-page')
            ->assertViewHas('communicationStatuses', fn (Collection $communicationStatuses): bool => $communicationStatuses->first()->code === CommunicationCode::WELCOME)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        Livewire::withQueryParams([
            'sort' => 'status',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'status')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'communicationStatuses',
                fn (Collection $communicationStatuses) => $direction === 'ASC'
                    ? $communicationStatuses->first()->code === CommunicationCode::COUNTER_OFFER_BUT_NO_RESPONSE
                    : $communicationStatuses->first()->code === CommunicationCode::WELCOME
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_trigger_type(string $direction): void
    {
        Livewire::withQueryParams([
            'sort' => 'trigger_type',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'trigger_type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'communicationStatuses',
                fn (Collection $communicationStatuses) => $direction === 'ASC'
                    ? $communicationStatuses->first()->trigger_type === CommunicationStatusTriggerType::AUTOMATIC
                    : $communicationStatuses->first()->trigger_type === CommunicationStatusTriggerType::BOTH
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_email_template_name(string $direction): void
    {
        $automatedEmailTemplate = AutomatedTemplate::factory()->email()->create(['name' => 'Test Email Template']);

        CommunicationStatus::query()
            ->where('code', CommunicationCode::NEW_ACCOUNT)
            ->update(['automated_email_template_id' => $automatedEmailTemplate->id]);

        Livewire::withQueryParams([
            'sort' => 'email_template_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'email_template_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'communicationStatuses',
                fn (Collection $communicationStatuses) => $direction === 'ASC'
                    ? is_null($communicationStatuses->first()->emailTemplate?->name)
                    : $communicationStatuses->first()->emailTemplate->name === 'Test Email Template'
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sms_template_name(string $direction): void
    {
        $automatedSmsTemplate = AutomatedTemplate::factory()->sms()->create(['name' => 'Test SMS Template']);

        CommunicationStatus::query()
            ->where('code', CommunicationCode::WELCOME)
            ->update(['automated_sms_template_id' => $automatedSmsTemplate->id]);

        Livewire::withQueryParams([
            'sort' => 'sms_template_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sms_template_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'communicationStatuses',
                fn (Collection $communicationStatuses) => $direction === 'ASC'
                    ? is_null($communicationStatuses->first()->smsTemplate?->name)
                    : $communicationStatuses->first()->smsTemplate->name === 'Test SMS Template'
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
