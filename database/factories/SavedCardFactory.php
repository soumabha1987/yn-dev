<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SavedCard;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedCard>
 */
class SavedCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cardNumber = fake()->creditCardNumber;
        return [
            'consumer_id' => Consumer::factory(), 
            'last4digit' => substr(preg_replace('/\D/', '', $cardNumber), -4),
            'card_holder_name' => fake()->name(),
            'expiry' => fake()->creditCardExpirationDate->format('m/y'),
            'encrypted_card_data' => encrypt($cardNumber),
        ];
    }
}
