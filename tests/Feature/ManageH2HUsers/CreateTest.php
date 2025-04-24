<?php

declare(strict_types=1);

namespace Tests\Feature\ManageH2HUsers;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManageH2HUsers\Create;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(Create::class, ['openModel' => true])
            ->assertViewIs('livewire.creditor.manage-h2h-users.create')
            ->assertOk();
    }

    #[Test]
    public function it_throw_required_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->assertViewIs('livewire.creditor.manage-h2h-users.create')
            ->call('create')
            ->assertHasErrors([
                'form.name' => ['required'],
                'form.email' => ['required'],
                'form.password' => ['required'],
            ])
            ->assertHasNoErrors(['form.phone_no'])
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_unique_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->assertViewIs('livewire.creditor.manage-h2h-users.create')
            ->set('form.name', fake()->name())
            ->set('form.email', $this->user->email)
            ->set('form.password', 'Dance@click21')
            ->set('form.phone_no', '4849999999')
            ->call('create')
            ->assertHasErrors(['form.email' => ['unique']])
            ->assertHasNoErrors(['form.name', 'form.password', 'form.phone_no'])
            ->assertOk();
    }

    #[Test]
    public function it_can_create_h2h_user(): void
    {
        Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->assertViewIs('livewire.creditor.manage-h2h-users.create')
            ->set('form.name', $name = fake()->name())
            ->set('form.email', $email = fake()->email())
            ->set('form.password', $password = 'Dance@us123')
            ->set('form.phone_no', $phone = '4848889999')
            ->call('create')
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog-box')
            ->assertOk();

        $user = User::whereNot('id', $this->user->id)->firstOrFail();

        $this->assertEquals($email, $user->email);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($phone, $user->phone_no);

        $this->assertTrue($user->is_h2h_user);
        $this->assertEquals($user->parent_id, $this->user->id);
        $this->assertEquals($user->company_id, $this->user->company_id);

        $this->assertTrue(Hash::check($password, $user->password));
    }
}
