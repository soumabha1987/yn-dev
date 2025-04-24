<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomatedTemplate;

use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\ListPage;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component_with_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.automated-templates'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automated-template.list-page')
            ->assertViewHas('automatedTemplates', fn (LengthAwarePaginator $automatedTemplates) => $automatedTemplates->isEmpty())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_automated_templates_with_some_data(): void
    {
        $automatedTemplate = AutomatedTemplate::factory()->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automated-template.list-page')
            ->assertViewHas('automatedTemplates', fn (LengthAwarePaginator $automatedTemplates) => $automatedTemplate->is($automatedTemplates->getCollection()->first()))
            ->assertSee(str($automatedTemplate->name)->words(3))
            ->assertSeeHtml('wire:click="delete(' . $automatedTemplate->id . ')"')
            ->assertOk();
    }

    #[Test]
    public function it_can_delete_automated_template(): void
    {
        $automatedTemplate = AutomatedTemplate::factory()->create();

        Livewire::test(ListPage::class)
            ->call('delete', $automatedTemplate)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertSoftDeleted($automatedTemplate);
    }

    #[Test]
    public function it_can_not_delete_when_this_automated_template_assign_to_communication_status(): void
    {
        $communicationStatus = CommunicationStatus::factory()
            ->for(AutomatedTemplate::factory(), 'emailTemplate')
            ->create([
                'code' => CommunicationCode::WELCOME,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        Livewire::test(ListPage::class)
            ->call('delete', $communicationStatus->emailTemplate)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertNotSoftDeleted($communicationStatus->emailTemplate);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $createdAutomatedTemplates = AutomatedTemplate::factory(14)
            ->sequence(fn (Sequence $sequence) => ['name' => range('A', 'Z')[$sequence->index]])
            ->create();

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedTemplates',
                fn (LengthAwarePaginator $automatedTemplates) => $direction === 'ASC'
                    ? $createdAutomatedTemplates->first()->is($automatedTemplates->getCollection()->first())
                    : $createdAutomatedTemplates->last()->is($automatedTemplates->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_type(string $direction): void
    {
        $createdAutomatedTemplates = AutomatedTemplate::factory(2)
            ->sequence(
                ['type' => AutomatedTemplateType::EMAIL],
                ['type' => AutomatedTemplateType::SMS],
            )
            ->create();

        Livewire::withQueryParams([
            'sort' => 'type',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedTemplates',
                fn (LengthAwarePaginator $automatedTemplates) => $direction === 'ASC'
                    ? $createdAutomatedTemplates->first()->is($automatedTemplates->getCollection()->first())
                    : $createdAutomatedTemplates->last()->is($automatedTemplates->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_subject(string $direction): void
    {
        $createdAutomatedTemplates = AutomatedTemplate::factory(14)
            ->sequence(fn (Sequence $sequence) => [
                'type' => AutomatedTemplateType::EMAIL,
                'subject' => range('A', 'Z')[$sequence->index],
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'subject',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'subject')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'automatedTemplates',
                fn (LengthAwarePaginator $automatedTemplates) => $direction === 'ASC'
                    ? $createdAutomatedTemplates->first()->is($automatedTemplates->getCollection()->first())
                    : $createdAutomatedTemplates->last()->is($automatedTemplates->getCollection()->first())
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
