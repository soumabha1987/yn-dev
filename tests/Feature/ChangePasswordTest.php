<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\ChangePasswordPage;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class ChangePasswordTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);

        $this->user->assignRole($role);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('change-password'))
            ->assertSeeLivewire(ChangePasswordPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_page_with_the_defined_view(): void
    {
        Livewire::test(ChangePasswordPage::class)
            ->assertViewIs('livewire.creditor.change-password-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_error_of_required(): void
    {
        Livewire::test(ChangePasswordPage::class)
            ->call('updatePassword')
            ->assertOk()
            ->assertHasErrors(['form.currentPassword', 'form.newPassword']);
    }

    #[Test]
    #[DataProvider('roles')]
    public function it_can_update_user_password(EnumsRole $role): void
    {
        if ($role !== EnumsRole::CREDITOR) {
            $role = Role::query()->create(['name' => $role]);
            $this->user->assignRole($role);
        }

        $this->user->update(['password' => bcrypt('test')]);

        Livewire::test(ChangePasswordPage::class)
            ->set('form.currentPassword', 'test')
            ->set('form.newPassword', 'Thanks@498')
            ->set('form.newPassword_confirmation', 'Thanks@498')
            ->call('updatePassword')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('Thanks@498', $this->user->refresh()->password));
    }

    public static function roles(): array
    {
        return [
            [EnumsRole::SUPERADMIN],
            [EnumsRole::CREDITOR],
        ];
    }
}
