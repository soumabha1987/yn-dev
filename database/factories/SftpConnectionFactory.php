<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\SftpConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SftpConnection>
 */
class SftpConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->word(),
            'host' => fake()->ipv4(),
            'port' => fake()->randomNumber(4, strict: true),
            'enabled' => fake()->boolean(),
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'import_filepath' => fake()->filePath(),
            'export_filepath' => fake()->filePath(),
        ];
    }
}
