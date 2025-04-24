<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Forms\Communication;

use App\Enums\CampaignFrequency;
use App\Enums\Role;
use App\Enums\TemplateType;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Template;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Form;

class CampaignForm extends Form
{
    public ?int $campaign_id = null;

    public $type = TemplateType::EMAIL->value;

    public $template_id = null;

    public $group_id = null;

    public string $start_date = '';

    public ?string $end_date = null;

    public ?string $frequency = null;

    public $day_of_week = null;

    public $day_of_month = null;

    public bool $is_run_immediately = false;

    public function init(Campaign $campaign): void
    {
        $this->fill([
            'campaign_id' => $campaign->id,
            'template_id' => $campaign->template_id,
            'type' => $campaign->template && $campaign->template->type !== TemplateType::E_LETTER ? $campaign->template->type->value : '',
            'group_id' => $campaign->group_id,
            'start_date' => $campaign->start_date->lte(today()) ? today()->toDateString() : $campaign->start_date->toDateString(),
            'end_date' => filled($campaign->end_date) ? $campaign->end_date->toDateString() : null,
            'frequency' => $campaign->frequency->value,
            'day_of_week' => $campaign->day_of_week ?? null,
            'day_of_month' => $campaign->day_of_month ?? null,
            'is_run_immediately' => $campaign->frequency === CampaignFrequency::ONCE && $campaign->start_date->lte(today()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $isCreditor = $user->hasRole(Role::CREDITOR);

        return [
            'type' => ['nullable', Rule::requiredIf(! $isCreditor), Rule::in(TemplateType::SMS->value, TemplateType::EMAIL->value)],
            'template_id' => [
                'required',
                'integer',
                Rule::exists(Template::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('company_id', $user->company_id)
                    ->when(
                        $isCreditor,
                        function (Exists $query): void {
                            $query->where('type', TemplateType::E_LETTER);
                        },
                        function (Exists $query): void {
                            $query->when(
                                $this->type === TemplateType::EMAIL->value,
                                fn (Exists $query): Exists => $query->where('type', TemplateType::EMAIL->value),
                                fn (Exists $query): Exists => $query->where('type', TemplateType::SMS->value),
                            );
                        }
                    ),
            ],
            'group_id' => [
                'required',
                'integer',
                Rule::exists(Group::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('company_id', $user->company_id),
            ],
            'frequency' => ['required', 'string', Rule::in(CampaignFrequency::values())],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:' . today()->addDay()->toDateString(),
                'before_or_equal:' . today()->addYear()->toDateString(),
            ],
            'end_date' => [
                'nullable',
                Rule::requiredIf($this->frequency !== CampaignFrequency::ONCE->value),
                'date',
                'date_format:Y-m-d',
                'after_or_equal:' . Carbon::parse($this->start_date)->toDateString(),
                'before_or_equal:' . Carbon::parse($this->start_date)->addYear()->toDateString(),
            ],
            'day_of_week' => [
                'nullable',
                Rule::requiredIf($this->frequency === CampaignFrequency::WEEKLY->value),
                'integer',
                Rule::in(array_keys(Carbon::getDays())),
            ],
            'day_of_month' => [
                'nullable',
                Rule::requiredIf($this->frequency === CampaignFrequency::MONTHLY->value),
                'integer',
                'min:0',
                'max:31',
            ],
            'is_run_immediately' => ['required', 'boolean'],
        ];
    }
}
