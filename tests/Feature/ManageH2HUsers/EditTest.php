<?php

declare(strict_types=1);

namespace Tests\Feature\ManageH2HUsers;

use App\Livewire\Creditor\ManageH2HUsers\Edit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditTest extends TestCase
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
        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'is_h2h_user' => true,
            'parent_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['user' => $user])
            ->assertViewIs('livewire.creditor.manage-h2h-users.edit')
            ->assertOk();
    }

    #[Test]
    public function it_can_ignore_current_model_email_for_update(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'is_h2h_user' => true,
            'parent_id' => $this->user->id,
        ]);

        Cache::shouldReceive('set')->with('user', $user->id)->once();
        Cache::shouldReceive('get')->with('user')->once()->andReturn($user->id);
        Cache::shouldReceive('forget')->with('user')->once();

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['user' => $user])
            ->assertViewIs('livewire.creditor.manage-h2h-users.edit')
            ->set('form.name', $name = fake()->name())
            ->call('update')
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog-box')
            ->assertOk();

        $this->assertEquals($name, $user->refresh()->name);
    }

    #[Test]
    public function it_can_not_ignore_other_models(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->user->company_id,
            'is_h2h_user' => true,
            'parent_id' => $this->user->id,
        ]);

        Cache::shouldReceive('set')->with('user', $user->id)->once();
        Cache::shouldReceive('get')->with('user')->once()->andReturn($user->id);

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['user' => $user])
            ->assertViewIs('livewire.creditor.manage-h2h-users.edit')
            ->set('form.email', $this->user->email)
            ->call('update')
            ->assertHasErrors(['form.email' => ['unique']])
            ->assertNotDispatched('close-dialog-box')
            ->assertOk();
    }
}
