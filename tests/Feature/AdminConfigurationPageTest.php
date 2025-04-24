<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminConfigurationSlug;
use App\Enums\FeatureName;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AdminConfigurationPage;
use App\Models\AdminConfiguration;
use App\Models\FeatureFlag;
use Database\Seeders\AdminConfigurationSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class AdminConfigurationPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_page_of_super_admin_configuration(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->get(route('super-admin.configurations'))
            ->assertSeeLivewire(AdminConfigurationPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_page_for_non_super_admin_user(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);

        $this->get(route('super-admin.configurations'))
            ->assertDontSeeLivewire(AdminConfigurationPage::class)
            ->assertStatus(403);
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_pag(): void
    {
        Livewire::test(AdminConfigurationPage::class)
            ->assertViewIs('livewire.creditor.admin-configuration-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page_with_configuration_data(): void
    {
        Artisan::call('db:seed', ['--class' => AdminConfigurationSeeder::class]);

        Livewire::test(AdminConfigurationPage::class)
            ->assertViewIs('livewire.creditor.admin-configuration-page')
            ->assertViewHas('adminConfigurations', fn (Collection $adminConfigurations) => $adminConfigurations->count() === 1)
            ->assertOk();
    }

    #[Test]
    public function it_can_validate_admin_configuration_is_required(): void
    {
        $adminConfiguration = AdminConfiguration::query()->create([
            'name' => AdminConfigurationSlug::EMAIL_RATE->displayName(),
            'slug' => AdminConfigurationSlug::EMAIL_RATE,
            'value' => '0.4',
        ]);

        Livewire::test(AdminConfigurationPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.admin-configuration-page')
            ->set('adminConfigurationValues.' . $adminConfiguration->id, '')
            ->call('updateConfiguration', $adminConfiguration->id)
            // Because we didn't set any public property for this.
            ->assertHasNoErrors();

        $this->assertEquals('0.4', $adminConfiguration->refresh()->value);
    }

    #[Test]
    public function it_can_update_feature_flag_status(): void
    {
        $featureFlag = FeatureFlag::query()->create([
            'feature_name' => FeatureName::SCHEDULE_EXPORT,
            'status' => false,
        ]);

        Livewire::test(AdminConfigurationPage::class)
            ->assertOk()
            ->call('updateStatus', $featureFlag->id)
            ->assertDispatched("updated-status-$featureFlag->id");

        $this->assertTrue($featureFlag->refresh()->status);
    }
}
