<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms;

use App\Models\CustomContent;
use App\Models\Subclient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TermsAndConditionsForm extends Form
{
    public ?string $subclient_id = '';

    public ?CustomContent $customContent = null;

    public string $content = '';

    public function init(CustomContent $customContent): void
    {
        $this->fill([
            'customContent' => $customContent,
            'subclient_id' => $customContent->subclient_id ?? 'all',
            'content' => $customContent->content ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'subclient_id' => [
                'required',
                Rule::when(
                    $this->subclient_id !== 'all',
                    [
                        Rule::exists(Subclient::class, 'id')
                            ->where('company_id', Auth::user()->company_id),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'content' => __('terms & conditions content'),
        ];
    }
}
