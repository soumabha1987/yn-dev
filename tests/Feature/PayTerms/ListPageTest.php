<?php

declare(strict_types=1);

namespace Tests\Feature\PayTerms;

use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\PayTerms\ListPage;
use App\Models\Group;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.pay-terms'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file(): void
    {
        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.pay-terms.list-page')
            ->assertViewHas('terms')
            ->assertSee(__('Sub Account Name'))
            ->assertSee(__('Sub ID#'))
            ->assertSee(__('Term Type'))
            ->assertSee(__('Settlement Discount'))
            ->assertSee(__('PayPlan Bal. Discount'))
            ->assertSee(__('Min. Monthly Payment %'))
            ->assertSee(__('Max. Days 1st Payment'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_no_result_found_when_we_dont_have_sub_clients_and_group(): void
    {
        $this->subclient->delete();

        Livewire::test(ListPage::class)
            ->assertViewHas('terms', fn (LengthAwarePaginator $terms) => $terms->getCollection()->doesntContain($this->subclient))
            ->assertOk();
    }

    #[Test]
    public function it_can_remove_sub_client_pay_terms(): void
    {
        $this->user->update(['subclient_id' => null]);

        $subclient = Subclient::factory()->create([
            'company_id' => $this->user->company_id,
            'pif_balance_discount_percent' => fake()->numberBetween(1, 100),
            'ppa_balance_discount_percent' => fake()->numberBetween(1, 100),
            'min_monthly_pay_percent' => fake()->numberBetween(1, 100),
            'max_days_first_pay' => fake()->numberBetween(1, 30),
            'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
            'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
            'max_first_pay_days' => fake()->numberBetween(100, 999),
        ]);

        Livewire::test(ListPage::class)
            ->call('removeTerm', $subclient->id, 'sub account')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertDatabaseHas(Subclient::class, [
            'id' => $subclient->id,
            'pif_balance_discount_percent' => null,
            'ppa_balance_discount_percent' => null,
            'min_monthly_pay_percent' => null,
            'max_days_first_pay' => null,
            'minimum_settlement_percentage' => null,
            'minimum_payment_plan_percentage' => null,
            'max_first_pay_days' => null,
        ]);
    }

    #[Test]
    public function it_can_remove_group_pay_terms(): void
    {
        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
            'pif_balance_discount_percent' => fake()->numberBetween(1, 100),
            'ppa_balance_discount_percent' => fake()->numberBetween(1, 100),
            'min_monthly_pay_percent' => fake()->numberBetween(1, 100),
            'max_days_first_pay' => fake()->numberBetween(1, 30),
            'minimum_settlement_percentage' => fake()->numberBetween(2, 30),
            'minimum_payment_plan_percentage' => fake()->numberBetween(2, 30),
            'max_first_pay_days' => fake()->numberBetween(100, 999),
        ]);

        Livewire::test(ListPage::class)
            ->call('removeTerm', $group->id, 'group')
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertDatabaseHas(Group::class, [
            'id' => $group->id,
            'pif_balance_discount_percent' => null,
            'ppa_balance_discount_percent' => null,
            'min_monthly_pay_percent' => null,
            'max_days_first_pay' => null,
            'minimum_settlement_percentage' => null,
            'minimum_payment_plan_percentage' => null,
            'max_first_pay_days' => null,
        ]);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->company->update(['company_name' => 'C']);
        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_name' => range('A', 'Z')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? $createdSubClients->first()->subclient_name === $terms->getCollection()->first()->terms_name
                    : $createdSubClients->last()->subclient_name === $terms->getCollection()->first()->terms_name
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_sub_id(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);
        $this->subclient->delete();

        $this->user->company->update(['pif_balance_discount_percent' => null]);

        $createdSubclient = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'unique_identification_number' => range('111', '999')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'sub_id',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'sub_id')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? $createdSubclient->first()->unique_identification_number === (int) $terms->getCollection()->first()->unique_identification_number
                    : $createdSubclient->last()->unique_identification_number === (int) $terms->getCollection()->first()->unique_identification_number
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_term_type(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        Livewire::withQueryParams([
            'sort' => 'type',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? $terms->getCollection()->first()->type === 'master'
                    : $terms->getCollection()->first()->type === 'sub account'
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_term_settlement_discount(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['pif_balance_discount_percent' => null]);

        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'pif_balance_discount_percent' => range('5', '30')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'pif-discount',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'pif-discount')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? (float) $createdSubClients->first()->pif_balance_discount_percent === $terms->getCollection()->first()->pif_balance_discount_percent
                    : (float) $createdSubClients->last()->pif_balance_discount_percent === $terms->getCollection()->first()->pif_balance_discount_percent
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_term_pay_plan_balance_discount(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['ppa_balance_discount_percent' => null]);

        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'ppa_balance_discount_percent' => range('5', '30')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'ppa-discount',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'ppa-discount')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? (float) $createdSubClients->first()->ppa_balance_discount_percent === $terms->getCollection()->first()->ppa_balance_discount_percent
                    : (float) $createdSubClients->last()->ppa_balance_discount_percent === $terms->getCollection()->first()->ppa_balance_discount_percent
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_term_minimum_monthly_payment(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['min_monthly_pay_percent' => null]);

        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'min_monthly_pay_percent' => range('5', '30')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'min-monthly-amount',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'min-monthly-amount')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? (float) $createdSubClients->first()->min_monthly_pay_percent == $terms->getCollection()->first()->min_monthly_pay_percent
                    : (float) $createdSubClients->last()->min_monthly_pay_percent == $terms->getCollection()->first()->min_monthly_pay_percent
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_max_days_first_pay(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['max_days_first_pay' => null]);

        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'max_days_first_pay' => range('5', '30')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'max-day',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'max-day')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? $createdSubClients->first()->max_days_first_pay === $terms->getCollection()->first()->max_days_first_pay
                    : $createdSubClients->last()->max_days_first_pay === $terms->getCollection()->first()->max_days_first_pay
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_max_days_first_pay_where_maximum_day_on_master_offer(string $direction): void
    {
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['max_days_first_pay' => 500]);

        $this->subclient->delete();

        $createdSubClients = Subclient::factory(5)
            ->sequence(fn (Sequence $sequence) => [
                'max_days_first_pay' => range('5', '30')[$sequence->index],
                'company_id' => $this->user->company_id,
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'max-day',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'max-day')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'terms',
                fn (LengthAwarePaginator $terms) => $direction === 'ASC'
                    ? $createdSubClients->first()->max_days_first_pay === $terms->getCollection()->first()->max_days_first_pay
                    : $this->company->max_days_first_pay === $terms->getCollection()->first()->max_days_first_pay
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
