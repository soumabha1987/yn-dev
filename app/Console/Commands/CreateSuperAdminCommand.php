<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\Role as EnumRole;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdminCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:superadmin
                        {email? : The email of the user}
                        {name? : The name of the user}
                        {phone? : The phone number of the user}
                        {password? : The password for the user}
                        {--force : Create a new user if doesnt exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superadmin';

    public function handle(): int
    {
        $email = $this->argument('email') ?: text(
            label: 'Enter email address whom you want to make superadmin',
            placeholder: 'superadmin@gmail.com',
            required: __('validation.required', ['attribute' => 'email']),
            validate: function (string $value) {
                try {
                    Validator::validate(['email' => $value], ['email' => ['email']]);
                } catch (Exception $exception) {
                    return $exception->getMessage();
                }
            }
        );

        $user = User::query()->withTrashed()->firstWhere('email', $email);

        $role = Role::query()->firstOrCreate(['name' => EnumRole::SUPERADMIN]);

        if ($user) {
            if ($user->deleted_at !== null) {
                $this->error('Our database has one deleted user exists');

                return Command::FAILURE;
            }

            $user->assignRole($role);

            $this->info('User has been made superadmin');

            return Command::SUCCESS;
        }

        $this->info('User does not exists.');

        if ($this->confirmToProceed('User does not exist. Do you want to create a new user?')) {
            $name = $this->argument('name') ?: text(
                label: 'Enter name',
                placeholder: 'Super Admin',
                required: __('validation.required', ['attribute' => 'name']),
            );

            $phone = $this->argument('phone') ?: text(
                label: 'Enter phone number',
                placeholder: '1234567890',
                required: __('validation.required', ['attribute' => 'phone']),
                validate: function (string $value) {
                    try {
                        Validator::validate(['phone' => $value], ['phone' => 'phone:US']);
                    } catch (Exception $exception) {
                        return $exception->getMessage();
                    }
                }
            );

            $password = $this->argument('password') ?: password(
                label: 'Enter password',
                placeholder: 'Enter password',
                required: 'The password is required.',
                validate: function (string $value) {
                    try {
                        Validator::validate(['password' => $value], ['password' => Password::defaults()]);
                    } catch (Exception $exception) {
                        return $exception->getMessage();
                    }
                }
            );

            $company = Company::query()->create([
                'owner_full_name' => $name,
                'owner_email' => $email,
                'is_super_admin_company' => true,
                'status' => CompanyStatus::CREATED,
            ]);

            $user = User::query()->create([
                'company_id' => $company->id,
                'email' => $email,
                'name' => $name,
                'phone_no' => $phone,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role);

            $this->info('User created successfully');

            return Command::SUCCESS;
        }

        $this->error('Operation aborted!');

        return Command::FAILURE;
    }
}
