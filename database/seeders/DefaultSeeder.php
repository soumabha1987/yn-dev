<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Console\Commands\CommunicationStatusCommand;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = collect(Role::values())
            ->map(fn (string $role): array => [
                'name' => $role,
                'guard_name' => Auth::getDefaultDriver(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        DB::table('roles')->insert($roles);

        Artisan::call(CommunicationStatusCommand::class);

        $this->call([
            AdminConfigurationSeeder::class,
            FeatureFlagSeeder::class,
            ReasonSeeder::class,
        ]);
    }
}
