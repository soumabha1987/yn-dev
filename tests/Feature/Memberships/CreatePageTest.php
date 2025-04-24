<?php

declare(strict_types=1);

namespace Tests\Feature\Memberships;

use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\Memberships\CreatePage;
use App\Models\Membership;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePageTest extends TestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.memberships.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(CreatePage::class)
            ->assertViewIs('livewire.creditor.memberships.create-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_required_validation(): void
    {
        Livewire::test(CreatePage::class)
            ->call('create')
            ->assertHasErrors([
                'form.name' => ['required'],
                'form.price' => ['required'],
                'form.fee' => ['required'],
                'form.e_letter_fee' => ['required'],
                'form.upload_accounts_limit' => ['required'],
                'form.frequency' => ['required'],
                'form.description' => ['required'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_unique_validation_on_membership(): void
    {
        $membership = Membership::factory()->create();

        Livewire::test(CreatePage::class)
            ->set('form.name', $membership->name)
            ->call('create')
            ->assertHasErrors([
                'form.name' => ['unique'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_create_membership(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.name', $name = fake()->word())
            ->set('form.price', $price = fake()->randomNumber(3))
            ->set('form.fee', $fee = fake()->numberBetween(1, 100))
            ->set('form.e_letter_fee', $e_letter_fee = fake()->randomFloat(2, 0.1, 25))
            ->set('form.upload_accounts_limit', $uploadAccountsLimit = fake()->numberBetween(1, 500))
            ->set('form.frequency', $frequency = fake()->randomElement(MembershipFrequency::values()))
            ->set('form.description', $description = fake()->sentence())
            ->set('form.features', fake()->randomElements(MembershipFeatures::names(), 2))
            ->call('create')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseHas(Membership::class, [
            'name' => $name,
            'price' => $price,
            'fee' => $fee,
            'e_letter_fee' => $e_letter_fee,
            'upload_accounts_limit' => $uploadAccountsLimit,
            'frequency' => $frequency,
            'description' => $description,
        ]);
    }
}
