<?php

declare(strict_types=1);

namespace Tests\Feature\TermsAndCondition;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\TermsAndConditions\IndexPage;
use App\Livewire\Creditor\TermsAndConditions\ListView;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Merchant;
use App\Models\Subclient;
use App\Services\SetupWizardService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.terms-conditions'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertSeeLivewire(ListView::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_page_with_data(): void
    {
        Livewire::test(IndexPage::class)
            ->assertViewIs('livewire.creditor.terms-and-conditions.index-page')
            ->assertSet('subclients', [
                'all' => 'master terms & conditions (minimum requirement)',
                $this->subclient->id => $this->subclient->subclient_name . '/' . $this->subclient->unique_identification_number,
            ])
            ->assertSee(__('Terms & Conditions Template'))
            ->assertOk();
    }

    #[Test]
    public function create_creditor_terms_and_condition(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $subclient = Subclient::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', $subclient->id)
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('form.subclient_id', '')
            ->assertSet('form.content', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function in_can_create_creditor_terms_and_condition_without_subclient_and_completed_setup_wizard_steps(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', 'all')
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('form.subclient_id', '')
            ->assertSet('form.content', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_can_create_creditor_terms_and_condition_when_setup_wizard_incompleted_and_redirects(): void
    {
        Subclient::query()->delete();

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', 'all')
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNotDispatched('refresh-list-view')
            ->assertRedirect(route('home'));

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_cannot_redirect_home_after_create_terms_and_condition_and_setup_wizard_is_incompleted(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', 'all')
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors();

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_can_update_creditor_master_terms(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $customContent = CustomContent::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities(fake()->randomHtml()),
        ]);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', 'all')
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('form.subclient_id', '')
            ->assertSet('form.content', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseCount(CustomContent::class, 1);

        $this->assertDatabaseHas(CustomContent::class, [
            'id' => $customContent->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_can_update_creditor_subclient_terms_and_conditions(): void
    {
        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $customContent = CustomContent::factory()
            ->for($subclient = Subclient::factory()->create(['company_id' => $this->user->company_id]))
            ->create([
                'company_id' => $this->user->company_id,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
                'content' => htmlentities(fake()->randomHtml()),
            ]);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', $subclient->id)
            ->set('form.content', $content = fake()->randomHtml())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('form.subclient_id', '')
            ->assertSet('form.content', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Terms & Conditions updates saved.'));

        $this->assertDatabaseCount(CustomContent::class, 1);

        $this->assertDatabaseHas(CustomContent::class, [
            'id' => $customContent->id,
            'company_id' => $this->user->company_id,
            'subclient_id' => $subclient->id,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_can_create_last_remain_wizard_setup(): void
    {
        Subclient::query()->delete();

        $this->user->company()->update(['status' => CompanyStatus::ACTIVE]);

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        CustomContent::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::ABOUT_US,
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

        $this->partialMock(SetupWizardService::class)
            ->shouldReceive('isLastRequiredStepRemaining')
            ->with($this->user)
            ->andReturn(true);

        Livewire::test(IndexPage::class)
            ->set('form.subclient_id', 'all')
            ->set('form.content', $content = '<p>Test Terms And Conditions</p>')
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertNotDispatched('refresh-list-view')
            ->assertRedirect(route('home'))
            ->assertSessionHas('show-wizard-completed-modal');

        $this->assertDatabaseHas(CustomContent::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => null,
            'type' => CustomContentType::TERMS_AND_CONDITIONS,
            'content' => htmlentities($content),
        ]);
    }

    #[Test]
    public function it_can_check_required_validation(): void
    {
        Livewire::test(IndexPage::class)
            ->call('createOrUpdate')
            ->assertHasErrors(['form.subclient_id', 'form.content'])
            ->assertSee([
                'The subclient id field is required',
                'The terms & conditions content field is required',
            ])
            ->assertNotDispatched('refresh-list-view')
            ->assertNoRedirect();

        Notification::assertNotNotified(__('Terms & Conditions updates saved.'));
    }

    #[Test]
    public function it_can_check_sub_client_is_exists_in_database_or_not_when_creating_terms_and_conditions(): void
    {
        Livewire::test(IndexPage::class)
            ->set('form.content', fake()->randomHtml())
            ->set('form.subclient_id', 333)
            ->call('createOrUpdate')
            ->assertHasErrors('form.subclient_id')
            ->assertSee([
                'The selected subclient id is invalid.',
            ])
            ->assertNotDispatched('refresh-list-view')
            ->assertNoRedirect();

        Notification::assertNotNotified(__('Terms & Conditions updates saved.'));
    }
}
