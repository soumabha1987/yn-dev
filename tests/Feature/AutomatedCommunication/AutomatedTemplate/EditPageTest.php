<?php

declare(strict_types=1);

namespace Tests\Feature\AutomatedCommunication\AutomatedTemplate;

use App\Enums\AutomatedTemplateType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\EditPage;
use App\Models\AutomatedTemplate;
use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditPageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.automated-templates.edit', ['automatedTemplate' => $automatedTemplate]))
            ->assertSeeLivewire(EditPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $user = User::factory()->create();

        $automatedTemplate = AutomatedTemplate::factory()->create(['user_id' => $user->id]);

        Livewire::test(EditPage::class, ['automatedTemplate' => $automatedTemplate])
            ->assertSet('form.automatedTemplate', $automatedTemplate)
            ->assertSet('form.name', $automatedTemplate->name)
            ->assertSet('form.type', $automatedTemplate->type->value)
            ->assertSet('form.content', $automatedTemplate->content)
            ->assertOk();
    }

    #[Test]
    public function it_can_update_email_automated_template(): void
    {
        $user = User::factory()->create();

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'user_id' => $user->id,
            'type' => AutomatedTemplateType::EMAIL,
        ]);

        Livewire::actingAs($user)
            ->test(EditPage::class, ['automatedTemplate' => $automatedTemplate])
            ->set('form.content', $content = fake()->randomHtml())
            ->set('form.subject', $subject = '<p> test updated subject </p>')
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.automated-templates'))
            ->assertOk();

        Notification::assertNotified('Your template has been updated!');

        $this->assertEquals($content, $automatedTemplate->refresh()->content);
        $this->assertEquals($subject, $automatedTemplate->subject);
    }

    #[Test]
    public function it_can_update_sms_automated_template(): void
    {
        $user = User::factory()->create();

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'user_id' => $user->id,
            'type' => AutomatedTemplateType::SMS,
        ]);

        Livewire::actingAs($user)
            ->test(EditPage::class, ['automatedTemplate' => $automatedTemplate])
            ->set('form.content', $content = fake()->randomHtml())
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.automated-templates'))
            ->assertOk();

        Notification::assertNotified('Your template has been updated!');

        $this->assertEquals($content, $automatedTemplate->refresh()->content);
        $this->assertNull($automatedTemplate->subject);
    }

    #[Test]
    public function it_can_change_automated_template_type(): void
    {
        $user = User::factory()->create();

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'user_id' => $user->id,
            'type' => AutomatedTemplateType::SMS,
        ]);

        Livewire::actingAs($user)
            ->test(EditPage::class, ['automatedTemplate' => $automatedTemplate])
            ->set('form.type', AutomatedTemplateType::EMAIL)
            ->set('form.content', $content = fake()->randomHtml())
            ->call('update')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertOk();

        Notification::assertNotified('Sorry, you can not edit template type');

        $this->assertNotEquals($content, $automatedTemplate->refresh()->content);
    }

    #[Test]
    public function it_can_set_content_when_dispatch_an_event(): void
    {
        $user = User::factory()->create();

        $automatedTemplate = AutomatedTemplate::factory()->create(['user_id' => $user->id]);

        Livewire::test(EditPage::class, ['automatedTemplate' => $automatedTemplate])
            ->dispatch('update-content', $html = fake()->randomHtml())
            ->assertSet('form.content', $html)
            ->assertOk();
    }
}
