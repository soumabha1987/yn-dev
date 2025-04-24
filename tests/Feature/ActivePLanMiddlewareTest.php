<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\AccountSettingsPage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ActivePLanMiddlewareTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_creditor_or_subclient_active_plan(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->companyMembership->update(['current_plan_end' => now()->subDay()]);

        $this->user->assignRole($role);

        $this->get(route('creditor.settings'))
            ->assertDontSeeLivewire(AccountSettingsPage::class)
            ->assertRedirectToRoute('creditor.membership-settings')
            ->assertStatus(302);
    }
}
