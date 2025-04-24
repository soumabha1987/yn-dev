<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\ELetter;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Enums\TemplateType;
use App\Livewire\Creditor\Communications\ELetter\IndexPage;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use App\Models\Template;
use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $this->user->update(['subclient_id' => null]);

        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::TERMS_AND_CONDITIONS],
                ['type' => CustomContentType::ABOUT_US]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()
            ->create([
                'subclient_id' => null,
                'company_id' => $this->user->company_id,
                'is_mapped' => true,
            ]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('creditor.communication.e-letters'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_livewire_e_letter_template_view_page(): void
    {
        Livewire::test(IndexPage::class)
            ->assertViewIs('livewire.creditor.communications.e-letter.index-page')
            ->assertSee(__('Submit'))
            ->assertDontSee(__('Edit Template'))
            ->assertOk();
    }

    #[Test]
    public function it_can_create_e_letter_template(): void
    {
        Livewire::test(IndexPage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.description', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.name', '')
            ->assertSet('form.description', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'name' => $name,
            'description' => $description,
            'type' => TemplateType::E_LETTER,
        ]);
    }

    #[Test]
    public function it_can_create_email_template(): void
    {
        $user = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user->assignRole($role);

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', $type = TemplateType::EMAIL)
            ->set('form.subject', $subject = fake()->text())
            ->set('form.description', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.name', '')
            ->assertSet('form.subject', '')
            ->assertSet('form.description', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'name' => $name,
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
        ]);
    }

    #[Test]
    public function it_can_update_email_template(): void
    {
        $user = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user->assignRole($role);

        $template = Template::factory()
            ->for($this->company)
            ->create(['type' => TemplateType::EMAIL]);

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('form.template', $template)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', $type = TemplateType::EMAIL)
            ->set('form.subject', $subject = fake()->text())
            ->set('form.description', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template', null)
            ->assertSet('form.name', '')
            ->assertSet('form.subject', '')
            ->assertSet('form.description', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'name' => $name,
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
        ]);
    }

    #[Test]
    public function it_can_create_sms_template(): void
    {
        $user = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user->assignRole($role);

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', $type = TemplateType::SMS)
            ->set('form.smsDescription', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.name', '')
            ->assertSet('form.smdDescription', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'name' => $name,
            'type' => $type,
            'subject' => null,
            'description' => $description,
        ]);
    }

    #[Test]
    public function it_can_update_sms_template(): void
    {
        $superAdmin = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $superAdmin->assignRole($role);

        $template = Template::factory()
            ->for($superAdmin->company)
            ->create([
                'type' => TemplateType::SMS,
            ]);

        Livewire::actingAs($superAdmin)
            ->test(IndexPage::class)
            ->set('form.template', $template)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', $type = TemplateType::SMS)
            ->set('form.smsDescription', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template', null)
            ->assertSet('form.name', '')
            ->assertSet('form.description', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'user_id' => $superAdmin->id,
            'company_id' => $superAdmin->company_id,
            'name' => $name,
            'type' => $type,
            'description' => $description,
        ]);
    }

    #[Test]
    public function it_can_update_change_template_type(): void
    {
        $superAdmin = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $superAdmin->assignRole($role);

        $template = Template::factory()
            ->for($superAdmin->company)
            ->create([
                'type' => TemplateType::SMS,
            ]);

        Livewire::actingAs($superAdmin)
            ->test(IndexPage::class)
            ->set('form.template', $template)
            ->set('form.name', $name = fake()->word())
            ->set('form.type', $type = TemplateType::EMAIL)
            ->set('form.description', $description = fake()->text())
            ->set('form.subject', $subject = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNotSet('form.template', null)
            ->assertNotSet('form.name', '')
            ->assertNotSet('form.description', '')
            ->assertNotDispatched('refresh-list-view');

        Notification::assertNotified(__('Sorry, you can not edit template type'));

        $this->assertDatabaseMissing(Template::class, [
            'id' => $template->id,
            'user_id' => $superAdmin->id,
            'company_id' => $superAdmin->company_id,
            'name' => $name,
            'type' => $type,
            'description' => $description,
            'subject' => $subject,
        ]);
    }

    #[Test]
    public function it_can_update_e_letter_template(): void
    {
        $template = Template::factory()
            ->for($this->user)
            ->for($this->company)
            ->create([
                'type' => TemplateType::E_LETTER,
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.template', $template)
            ->set('form.name', $name = fake()->word())
            ->set('form.description', $description = fake()->text())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template', null)
            ->assertSet('form.name', '')
            ->assertSet('form.description', '')
            ->assertDispatched('refresh-list-view');

        $this->assertDatabaseHas(Template::class, [
            'id' => $template->id,
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'name' => $name,
            'description' => $description,
            'type' => TemplateType::E_LETTER,
        ]);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_create_or_update_e_letter_validation(array $requestData, array $requestError): void
    {
        Template::factory()
            ->for($this->company)
            ->for($this->user)
            ->create([
                'name' => 'Added template',
            ]);

        Livewire::test(IndexPage::class)
            ->set($requestData)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($requestError);
    }

    #[Test]
    public function it_can_set_description(): void
    {
        Livewire::test(IndexPage::class)
            ->dispatch('update-description', $description = fake()->randomHtml())
            ->assertSet('form.description', $description)
            ->assertOk();
    }

    public static function requestValidation(): array
    {
        return [
            [
                [],
                [
                    'form.name' => ['required'],
                    'form.description' => ['required'],
                ],
            ],
            [
                [
                    'form.name' => str('A')->repeat(256),
                ],
                [
                    'form.name' => ['max:255'],
                ],
            ],
            [
                [
                    'form.name' => 'Added template',
                ],
                [
                    'form.name' => ['unique'],
                ],
            ],
        ];
    }
}
