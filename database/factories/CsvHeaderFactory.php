<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FileUploadHistoryDateFormat;
use App\Models\Company;
use App\Models\CsvHeader;
use App\Models\Subclient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvHeader>
 */
class CsvHeaderFactory extends Factory
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
            'subclient_id' => Subclient::factory(),
            'name' => fake()->name(),
            'date_format' => fake()->randomElement(FileUploadHistoryDateFormat::values()),
            'is_mapped' => fake()->boolean(),
            'headers' => fake()->randomElements($this->faker->words(10), $this->faker->numberBetween(1, 10)),
        ];
    }
}
