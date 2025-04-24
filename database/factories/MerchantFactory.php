<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantName;
use App\Enums\MerchantType;
use App\Models\Company;
use App\Models\Merchant;
use App\Models\Subclient;
use BackedEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
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
            'subclient_id' => fake()->boolean() ? Subclient::factory() : null,
            'merchant_name' => fake()->randomElement(MerchantName::values()),
            'merchant_type' => function (array $attributes): string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::STRIPE->value ? MerchantType::CC->value : fake()->randomElement(MerchantType::values());
            },
            'usaepay_key' => function (array $attributes): ?string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::USA_EPAY->value ? fake()->uuid() : null;
            },
            'usaepay_pin' => function (array $attributes): ?string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::USA_EPAY->value ? fake()->uuid() : null;
            },
            'authorize_login_id' => function (array $attributes): ?string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::AUTHORIZE->value ? fake()->uuid() : null;
            },
            'authorize_transaction_key' => function (array $attributes): ?string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::AUTHORIZE->value ? fake()->uuid() : null;
            },
            'stripe_secret_key' => function (array $attributes): ?string {
                $attributes['merchant_name'] = $attributes['merchant_name'] instanceof BackedEnum ? $attributes['merchant_name']->value : $attributes['merchant_name'];

                return $attributes['merchant_name'] === MerchantName::STRIPE->value ? fake()->uuid() : null;
            },
            'verified_at' => fake()->dateTimeBetween(),
        ];
    }
}
