<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Subclient;
use App\Rules\AddressSingleSpace;
use App\Rules\SingleSpace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SubclientForm extends Form
{
    public ?Subclient $subclient = null;

    public string $subclient_name = '';

    public string $company_id = '';

    public string $unique_identification_number = '';

    public function init(Subclient $subclient): void
    {
        $this->fill([
            'subclient' => $subclient,
            'company_id' => $subclient->company_id,
            'subclient_name' => $subclient->subclient_name ?? '',
            'unique_identification_number' => $subclient->unique_identification_number,
        ]);
    }

    public function rules(): array
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(Role::SUPERADMIN);

        return [
            'company_id' => [
                Rule::when($isSuperAdmin, [
                    'required',
                    Rule::exists(Company::class, 'id')
                        ->where('is_super_admin_company', false),
                ]),
            ],
            'subclient_name' => [
                'required',
                'min:2',
                'max:160',
                new AddressSingleSpace,
                Rule::unique(Subclient::class, 'subclient_name')
                    ->where('company_id', $isSuperAdmin ? $this->company_id : $user->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->subclient?->id),
            ],
            'unique_identification_number' => [
                'required',
                'max:160',
                new SingleSpace,
                Rule::unique(Subclient::class, 'unique_identification_number')
                    ->where('company_id', $isSuperAdmin ? $this->company_id : $user->company_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->subclient?->id),
            ],
        ];
    }
}
