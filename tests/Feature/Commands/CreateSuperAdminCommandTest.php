<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\CreateSuperAdminCommand;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    #[Test]
    public function it_can_create_superadmin_to_existing_user_with_asking_question(): void
    {
        $user = User::factory()->create(['email' => 'user@gmail.com']);

        $this->artisan(CreateSuperAdminCommand::class)
            ->expectsQuestion('Enter email address whom you want to make superadmin', 'user@gmail.com')
            ->assertOk();

        $this->assertTrue($user->hasRole(Role::SUPERADMIN));
    }

    #[Test]
    public function it_can_create_superadmin_to_existing_user_without_asking_question(): void
    {
        $user = User::factory()->create(['email' => 'user@gmail.com']);

        $this->artisan(CreateSuperAdminCommand::class, ['email' => 'user@gmail.com'])
            ->expectsOutput('User has been made superadmin')
            ->assertOk();

        $this->assertTrue($user->hasRole(Role::SUPERADMIN));
    }

    #[Test]
    public function it_can_not_allow_deleted_user_email(): void
    {
        $user = User::factory()->create(['email' => 'user@gmail.com']);

        $user->delete();

        $this->artisan(CreateSuperAdminCommand::class, ['email' => 'user@gmail.com'])
            ->expectsOutput('Our database has one deleted user exists')
            ->assertFailed();

        $this->assertFalse($user->hasRole(Role::SUPERADMIN));
    }

    #[Test]
    public function it_can_create_new_user_once_user_is_not_found_with_asking_question(): void
    {
        Company::factory()->create(['id' => 1]);

        $this->artisan(CreateSuperAdminCommand::class)
            ->expectsQuestion('Enter email address whom you want to make superadmin', 'user@gmail.com')
            ->expectsQuestion('Enter name', 'Test User')
            ->expectsQuestion('Enter phone number', '4849998888')
            ->expectsQuestion('Enter password', 'Pass@123')
            ->assertOk();

        $user = User::query()->firstOrFail();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('user@gmail.com', $user->email);
        $this->assertTrue(Hash::check('Pass@123', $user->password));
        $this->assertTrue($user->hasRole(Role::SUPERADMIN));
    }

    #[Test]
    public function it_can_check_password_validation(): void
    {
        $this->artisan(CreateSuperAdminCommand::class)
            ->expectsQuestion('Enter email address whom you want to make superadmin', 'user@gmail.com')
            ->expectsQuestion('Enter name', 'Test User')
            ->expectsQuestion('Enter phone number', '4849991234')
            ->expectsQuestion('Enter password', 'pass')
            ->assertFailed();
    }

    #[Test]
    public function it_can_check_phone_validation(): void
    {
        $this->artisan(CreateSuperAdminCommand::class)
            ->expectsQuestion('Enter email address whom you want to make superadmin', 'user@gmail.com')
            ->expectsQuestion('Enter name', 'Test User')
            ->expectsQuestion('Enter phone number', '123456789')
            ->assertFailed();
    }
}
