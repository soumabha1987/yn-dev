<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomatedTemplate;

use App\Enums\AutomatedTemplateType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\CreatePage;
use App\Models\AutomatedTemplate;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.automated-templates.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(CreatePage::class)
            ->assertViewIs('livewire.creditor.automated-communication.automated-template.create-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation_when_creating_template(): void
    {
        Livewire::test(CreatePage::class)
            ->call('create')
            ->assertHasErrors([
                'form.name' => ['required'],
                'form.subject' => ['required'],
                'form.content' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_create_sms_automated_template(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', AutomatedTemplateType::SMS)
            ->set('form.content', fake()->sentence())
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(AutomatedTemplate::class, [
            'user_id' => $user->id,
            'type' => AutomatedTemplateType::SMS,
            'name' => $name,
            'subject' => null,
        ]);
    }

    #[Test]
    public function it_can_create_email_automated_template(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.content', fake()->randomHtml())
            ->set('form.subject', fake()->sentence())
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(AutomatedTemplate::class, [
            'user_id' => $user->id,
            'type' => AutomatedTemplateType::EMAIL->value,
            'name' => $name,
        ]);
    }

    #[Test]
    public function it_can_set_content(): void
    {
        Livewire::test(CreatePage::class)
            ->dispatch('update-content', $content = fake()->randomHtml())
            ->assertSet('form.content', $content)
            ->assertOk();
    }
}
