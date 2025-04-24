<?php

declare(strict_types=1);

namespace Tests\Feature\ManageH2HUsers;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManageH2HUsers\Create;
use App\Livewire\Creditor\ManageH2HUsers\ListPage;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get(route('super-admin.manage-h2h-users'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(ListPage::class, ['create' => true])
            ->assertViewIs('livewire.creditor.manage-h2h-users.list-page')
            ->assertViewHas('users', fn (LengthAwarePaginator $users) => $users->isEmpty())
            ->assertSeeLivewire(Create::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_with_some_data(): void
    {
        $user = User::factory()->create([
            'parent_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'is_h2h_user' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->assertViewIs('livewire.creditor.manage-h2h-users.list-page')
            ->assertViewHas('users', fn (LengthAwarePaginator $users) => $user->is($users->getCollection()->first()))
            ->assertOk();
    }

    #[Test]
    public function it_can_soft_delete_h2h_user(): void
    {
        $user = User::factory()->create([
            'parent_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'is_h2h_user' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $user)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertSoftDeleted($user);
    }

    #[Test]
    public function it_can_not_soft_deleted_h2h_user(): void
    {
        $user = User::factory()->create([
            'parent_id' => $this->user->id,
            'is_h2h_user' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ListPage::class)
            ->call('delete', $user)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertNotSoftDeleted($user);
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_name(string $direction): void
    {
        $createdUsers = User::factory(4)
            ->sequence(
                ['name' => 'A Test'],
                ['name' => 'B Test'],
                ['name' => 'C Test'],
                ['name' => 'D Test'],
            )
            ->create([
                'parent_id' => $this->user->id,
                'company_id' => $this->user->company_id,
                'is_h2h_user' => true,
            ]);

        Livewire::withQueryParams(['direction' => $direction === 'ASC'])
            ->actingAs($this->user)
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'users',
                fn (LengthAwarePaginator $users) => $direction === 'ASC'
                    ? $createdUsers->first()->is($users->getCollection()->first())
                    : $createdUsers->last()->is($users->getCollection()->first())
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
