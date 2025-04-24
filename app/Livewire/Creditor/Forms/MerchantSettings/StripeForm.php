<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\MerchantSettings;

use App\Models\Merchant;
use Livewire\Form;

class StripeForm extends Form
{
    public string $stripe_secret_key = '';

    public function setData(Merchant $merchant): void
    {
        $this->fill([
            'stripe_secret_key' => $merchant->stripe_secret_key,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stripe_secret_key' => ['required', 'string'],
        ];
    }
}
