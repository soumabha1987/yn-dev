<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\Partner;
use App\Rules\MultipleEmails;
use App\Rules\NamingRule;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PartnerForm extends Form
{
    public ?Partner $partner = null;

    public string $name = '';

    public string $contact_first_name = '';

    public string $contact_last_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $revenue_share;

    public string $creditors_quota;

    public string $report_emails = '';

    public function setup(Partner $partner): void
    {
        $this->fill([
            'partner' => $partner,
            'name' => $partner->name,
            'contact_first_name' => $partner->contact_first_name,
            'contact_last_name' => $partner->contact_last_name ?? '',
            'contact_email' => $partner->contact_email,
            'contact_phone' => $partner->contact_phone,
            'revenue_share' => $partner->revenue_share,
            'creditors_quota' => $partner->creditors_quota,
            'report_emails' => implode(',', $partner->report_emails),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:50', new NamingRule, Rule::unique(Partner::class)->ignore($this->partner?->id)],
            'contact_first_name' => ['required', 'string', 'min:3', 'max:50', new NamingRule],
            'contact_last_name' => ['required', 'string', 'min:3', 'max:50', new NamingRule],
            'contact_email' => ['required', 'email', 'min:3', 'max:50'],
            'contact_phone' => ['required', 'phone:US'],
            'revenue_share' => ['required', 'numeric', 'min:0', 'max:99', 'regex:/^\d+(\.\d+)?$/'],
            'creditors_quota' => ['required', 'integer', 'gt:0', 'regex:/^\d+$/'],
            'report_emails' => ['required', 'string', new MultipleEmails],
        ];
    }
}
