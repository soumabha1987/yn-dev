<?php

declare(strict_types=1);

namespace Tests\Feature\AboutUs;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AboutUs\CreateOrUpdatePage;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class CreateOrUpdatePageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.about-us.create-or-update'))
            ->assertOk()
            ->assertSeeLivewire(CreateOrUpdatePage::class);
    }

    #[Test]
    public function it_can_render_the_view_page_with_data(): void
    {
        Livewire::test(CreateOrUpdatePage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.about-us.create-or-update-page')
            ->assertSet('form.content', '');
    }

    #[Test]
    public function it_can_render_required_validation_errors(): void
    {
        CustomContent::factory()
            ->for($this->user->company)
            ->create([
                'subclient_id' => null,
                'type' => CustomContentType::ABOUT_US,
            ]);

        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors(['form.content' => ['required']]);
    }

    #[Test]
    public function it_can_render_no_validation_errors(): void
    {
        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '<p>Test About Us</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();
    }

    #[Test]
    public function it_can_render_create_default_about_us_when_completed_setup_wizard_steps(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '<p>Test About Us</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::ABOUT_US,
        ]);
    }

    #[Test]
    public function it_can_render_create_default_about_us_when_in_completed_setup_wizard_steps(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '<p>Test About Us</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::ABOUT_US,
        ]);
    }

    #[Test]
    public function it_can_create_last_remain_wizard_setup(): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        CustomContent::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
        ]);

        Merchant::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'headers' => [
                'EMAIL_ID' => ConsumerFields::CONSUMER_EMAIL->value,
            ],
        ]);

        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '<p>Test About Us</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSessionHas('show-wizard-completed-modal');

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::ABOUT_US,
        ]);
    }

    #[Test]
    public function it_can_throw_validation_error_when_markdown_content_has_no_data(): void
    {
        Livewire::test(CreateOrUpdatePage::class)
            ->set('form.content', '<p><br></p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors('form.content');
    }

    #[Test]
    public function it_can_render_update_about_us(): void
    {
        $customContent = CustomContent::factory()
            ->for($this->user->company)
            ->create([
                'subclient_id' => null,
                'type' => CustomContentType::ABOUT_US,
                'content' => $content = fake()->randomHtml(),
            ]);

        Livewire::test(CreateOrUpdatePage::class, ['customContent' => $customContent])
            ->set('form.content', '<p>Update About Us</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertNotEquals($customContent->refresh()->content, $content);
    }
}
