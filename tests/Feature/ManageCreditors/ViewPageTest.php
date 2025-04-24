<?php

declare(strict_types=1);

namespace Tests\Feature\ManageCreditors;

use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Livewire\Creditor\ManageCreditors\ViewPage;
use App\Models\Company;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Models\ScheduleTransaction;
use App\Models\User;
use Illuminate\Http\Response;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ViewPageTest extends AuthTestCase
{
    public Company $company;

    public function it_can_access_forbidden_for_non_super_admin_user(): void
    {
        $company = Company::factory()->create();

        $this->get(route('super-admin.manage-creditors.view', ['company' => $company->id]))
            ->assertDontSeeLivewire(ViewPage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->superAdminAuthenticationAccessAndCompany();

        Livewire::test(ViewPage::class, ['company' => $this->company])
            ->assertSeeLivewire(ViewPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_aborts_if_company_has_no_creditor_user(): void
    {
        $role = Role::query()->firstOrCreate(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->assertNull($this->company->creditorUser);

        Livewire::test(ViewPage::class, ['company' => $this->company])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function it_can_display_count_company_consumer_transaction_amount_and_merchant_details_livewire_component(): void
    {
        $this->superAdminAuthenticationAccessAndCompany();

        Merchant::factory()
            ->create([
                'company_id' => $this->company->id,
                'merchant_name' => MerchantName::AUTHORIZE,
                'merchant_type' => MerchantType::CC,
            ]);

        $consumers = Consumer::factory($count = 10)->create(['company_id' => $this->company->id]);

        ScheduleTransaction::factory()
            ->for($consumers->first())
            ->create([
                'company_id' => $this->company->id,
                'status' => TransactionStatus::SCHEDULED->value,
                'amount' => 10,
            ]);

        Livewire::test(ViewPage::class, ['company' => $this->company])
            ->assertDontSee(MerchantName::USA_EPAY->value)
            ->assertViewHasAll([
                'scheduleTransactionAmount' => '10.00',
                'consumerCount' => $count,
                'merchantName' => MerchantName::AUTHORIZE->value,
                'company' => $this->company,
                'ccMerchant' => MerchantType::CC->value,
                'achMerchant' => false,
            ])
            ->assertOk();
    }

    private function superAdminAuthenticationAccessAndCompany(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->company = Company::factory()->create();

        $creditorUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $creditorRole = Role::query()->firstOrCreate(['name' => EnumRole::CREDITOR]);

        $creditorUser->assignRole($creditorRole);
    }
}
