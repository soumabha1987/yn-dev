<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\PersonalizedLogoPage;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use App\Models\PersonalizedLogo;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class PersonalizedLogoPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_page(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.personalized-logo-and-link'))
            ->assertSeeLivewire(PersonalizedLogoPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_the_personalized_logo(): void
    {
        Livewire::test(PersonalizedLogoPage::class)
            ->assertViewIs('livewire.creditor.personalized-logo-page')
            ->assertSet('personalizedLogo', null)
            ->assertSet('form.personalizedLogo', null)
            ->assertSet('form.primary_color', '#0079f2')
            ->assertSet('form.secondary_color', '#000000')
            ->assertSet('form.size', 320)
            ->assertOk();
    }

    #[Test]
    public function it_can_create_the_personalized_logo_for_subclient(): void
    {
        Livewire::test(PersonalizedLogoPage::class)
            ->set('form.primary_color', $primaryColor = fake()->hexColor())
            ->set('form.secondary_color', $secondaryColor = fake()->hexColor())
            ->set('form.size', $size = fake()->numberBetween(160, 520))
            ->call('createOrUpdate')
            ->assertDispatched('set-header-logo')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(PersonalizedLogo::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'size' => $size,
            'customer_communication_link' => 'consumer',
        ]);
    }

    #[Test]
    public function it_can_create_the_personalized_logo_for_company_when_completed_setup_wizard_steps(): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

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

        CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'headers' => [
                'EMAIL_ID' => ConsumerFields::CONSUMER_EMAIL->value,
            ],
        ]);

        Livewire::test(PersonalizedLogoPage::class)
            ->set('form.primary_color', $primaryColor = fake()->hexColor())
            ->set('form.secondary_color', $secondaryColor = fake()->hexColor())
            ->call('createOrUpdate')
            ->assertDispatched('set-header-logo')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertNoRedirect();

        $this->assertDatabaseHas(PersonalizedLogo::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'customer_communication_link' => 'consumer',
        ]);
    }

    #[Test]
    public function it_can_create_the_personalized_logo_for_company_when_in_completed_setup_wizard_steps(): void
    {
        $this->user->update(['subclient_id' => null]);

        Livewire::test(PersonalizedLogoPage::class)
            ->set('form.primary_color', $primaryColor = fake()->hexColor())
            ->set('form.secondary_color', $secondaryColor = fake()->hexColor())
            ->set('form.size', $size = fake()->numberBetween(160, 520))
            ->call('createOrUpdate')
            ->assertDispatched('set-header-logo')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertRedirectToRoute('home');

        $this->assertDatabaseHas(PersonalizedLogo::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'customer_communication_link' => 'consumer',
        ]);
    }

    #[Test]
    public function it_can_set_mount_property_for_company(): void
    {
        $this->user->update(['subclient_id' => null]);

        $personalizedLogo = PersonalizedLogo::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'customer_communication_link' => 'consumer',
        ]);

        Livewire::test(PersonalizedLogoPage::class)
            ->assertSet('form.primary_color', $personalizedLogo->primary_color)
            ->assertSet('form.secondary_color', $personalizedLogo->secondary_color)
            ->assertSet('form.size', $personalizedLogo->size)
            ->assertOk();
    }

    #[Test]
    public function it_can_set_mount_property_for_subclient(): void
    {
        $personalizedLogo = PersonalizedLogo::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
        ]);

        Livewire::test(PersonalizedLogoPage::class)
            ->assertSet('form.primary_color', $personalizedLogo->primary_color)
            ->assertSet('form.secondary_color', $personalizedLogo->secondary_color)
            ->assertSet('form.size', $personalizedLogo->size)
            ->assertOk();
    }

    #[Test]
    public function it_can_reset_and_refresh_creditor_personalized_logo(): void
    {
        $this->user->update(['subclient_id' => null]);

        $personalizedLogo = PersonalizedLogo::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'size' => 321,
        ]);

        Livewire::test(PersonalizedLogoPage::class)
            ->call('resetAndSave')
            ->assertSet('form.primary_color', '#0079f2')
            ->assertSet('form.secondary_color', '#000000')
            ->assertSet('form.size', 320)
            ->assertNotSet('form.primary_color', $personalizedLogo->primary_color)
            ->assertNotSet('form.secondary_color', $personalizedLogo->secondary_color)
            ->assertNotSet('form.size', $personalizedLogo->size)
            ->assertDispatched('set-header-logo')
            ->assertOk();

        $this->assertModelMissing($personalizedLogo);
    }
}
