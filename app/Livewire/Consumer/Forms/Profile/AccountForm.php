<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms\Profile;

use App\Enums\State;
use App\Models\Consumer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Livewire\Form;

class AccountForm extends Form
{
    public string $first_name = '';

    public string $last_name;

    public string $dob;

    public string $last_four_ssn;

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public function init(Consumer $consumer): void
    {
        $consumer->loadMissing('consumerProfile');

        $profileDetails = $consumer->consumerProfile;

        $this->fill([
            'first_name' => $profileDetails->first_name ?? $consumer->first_name ?? '',
            'last_name' => $consumer->last_name,
            'dob' => $consumer->dob->format('F j, Y'),
            'last_four_ssn' => $consumer->last4ssn,
            'address' => $profileDetails->address ?? ($consumer->address1 ?? '') . ($consumer->address2 ? ' ' . $consumer->address2 : ''),
            'city' => $profileDetails->city ?? $consumer->city ?? '',
            'state' => $profileDetails->state ?? $consumer->state ?? '',
            'zip' => $profileDetails->zip ?? $consumer->zip ?? '',
        ]);
    }

    /**
     * @return array<string, array<string | In>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:25'],
            'state' => ['required', 'string', Rule::in(State::values())],
            'zip' => ['required', 'string', 'numeric', 'max_digits:5'],
        ];
    }
}
