<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Communications;

use App\Enums\GroupConsumerState;
use App\Enums\GroupCustomRules;
use App\Enums\State;
use App\Models\Group;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class GroupForm extends Form
{
    public ?int $group_id = null;

    public string $name = '';

    public string $consumer_state = '';

    public array $custom_rules = [];

    public function init(Group $group): void
    {
        $this->fill([
            'group_id' => $group->id,
            'name' => $group->name,
            'consumer_state' => $group->consumer_state->value,
            'custom_rules' => filled($group->custom_rules) ? $group->custom_rules : [],
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Group::class)
                    ->ignore($this->group_id)
                    ->whereNull('deleted_at')
                    ->where('user_id', Auth::id()),
            ],
            'consumer_state' => [
                'required',
                'string',
                Rule::in(GroupConsumerState::values()),
            ],
            'custom_rules' => ['nullable', 'array'],
            'custom_rules.' . GroupCustomRules::UPLOAD_DATE->value => ['nullable', 'array', 'size:2'],
            'custom_rules.' . GroupCustomRules::UPLOAD_DATE->value . '.start_date' => ['nullable', 'string', 'date', 'date_format:Y-m-d'],
            'custom_rules.' . GroupCustomRules::UPLOAD_DATE->value . '.end_date' => ['nullable', 'string', 'date', 'date_format:Y-m-d'],
            'custom_rules.' . GroupCustomRules::PLACEMENT_DATE->value => ['nullable', 'array', 'size:2'],
            'custom_rules.' . GroupCustomRules::PLACEMENT_DATE->value . '.start_date' => ['nullable', 'string', 'date', 'date_format:Y-m-d'],
            'custom_rules.' . GroupCustomRules::PLACEMENT_DATE->value . '.end_date' => ['nullable', 'string', 'date', 'date_format:Y-m-d'],
            'custom_rules.' . GroupCustomRules::BALANCE_RANGE->value => ['nullable', 'array', 'size:2'],
            'custom_rules.' . GroupCustomRules::BALANCE_RANGE->value . '.minimum_balance' => ['nullable', 'numeric'],
            'custom_rules.' . GroupCustomRules::BALANCE_RANGE->value . '.maximum_balance' => ['nullable', 'numeric'],
            'custom_rules.' . GroupCustomRules::STATE->value => ['nullable', 'string', Rule::in(State::values())],
            'custom_rules.' . GroupCustomRules::CITY->value => ['nullable', 'string'],
            'custom_rules.' . GroupCustomRules::ZIP->value => ['nullable', 'string', 'max_digits:5'],
            'custom_rules.' . GroupCustomRules::DATE_OF_BIRTH->value . '.from_year' => ['nullable', 'date_format:Y', 'max_digits:4'],
            'custom_rules.' . GroupCustomRules::DATE_OF_BIRTH->value . '.to_year' => ['nullable', 'date_format:Y', 'max_digits:4', 'gt:custom_rules.' . GroupCustomRules::DATE_OF_BIRTH->value . '.from_year'],
            'custom_rules.' . GroupCustomRules::EXPIRATION_DATE->value . '.from_year' => ['nullable', 'date_format:Y', 'max_digits:4'],
            'custom_rules.' . GroupCustomRules::EXPIRATION_DATE->value . '.to_year' => ['nullable', 'date_format:Y', 'max_digits:4', 'gt:custom_rules.' . GroupCustomRules::EXPIRATION_DATE->value . '.from_year'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'custom_rules.' . GroupCustomRules::DATE_OF_BIRTH->value . '.to_year.gt' => __('To Year must be greater than the From Year.'),
            'custom_rules.' . GroupCustomRules::EXPIRATION_DATE->value . '.to_year.gt' => __('To Year must be greater than the From Year.'),
        ];
    }
}
