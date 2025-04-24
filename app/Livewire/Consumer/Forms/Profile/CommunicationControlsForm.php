<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Forms\Profile;

use App\Models\ConsumerProfile;
use Livewire\Form;

class CommunicationControlsForm extends Form
{
    public bool $text_permission = false;

    public bool $email_permission = false;

    public bool $landline_call_permission = false;

    public bool $usps_permission = false;

    public string $email = '';

    public string $mobile = '';

    public string $landline = '';

    public function init(ConsumerProfile $consumerProfile): void
    {
        $this->fill([
            'text_permission' => $consumerProfile->text_permission,
            'email_permission' => $consumerProfile->email_permission,
            'landline_call_permission' => $consumerProfile->landline_call_permission,
            'usps_permission' => $consumerProfile->usps_permission,
            'email' => $consumerProfile->email ?? '',
            'mobile' => $consumerProfile->mobile ?? '',
            'landline' => $consumerProfile->landline ?? '',
        ]);
    }
}
