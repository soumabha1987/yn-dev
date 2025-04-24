<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\Group;

use App\Enums\GroupConsumerState;
use App\Enums\GroupCustomRules;
use App\Enums\Role;
use App\Livewire\Creditor\Forms\Communications\GroupForm;
use App\Models\Consumer;
use App\Models\Group;
use App\Models\User;
use App\Services\SetupWizardService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Validation\Validator;
use Livewire\Component;

class IndexPage extends Component
{
    public GroupForm $form;

    public ?int $groupSize = null;

    public string $totalBalance = '';

    public bool $openModal = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function boot(): void
    {
        $this->form->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    $this->dispatch('update-custom-rules');
                }
            });
        });
    }

    public function mount(): void
    {
        if ($this->user->hasRole(Role::CREDITOR) && app(SetupWizardService::class)->getRemainingStepsCount($this->user) !== 0) {
            $this->redirectRoute('creditor.setup-wizard', navigate: true);
        }
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['user_id'] = $this->user->id;
        $validatedData['company_id'] = $this->user->company_id;
        $validatedData['custom_rules'] = $validatedData['custom_rules'] ?? null;

        if ($validatedData['custom_rules']) {
            $dateFields = [GroupCustomRules::DATE_OF_BIRTH->value, GroupCustomRules::EXPIRATION_DATE->value];

            foreach ($dateFields as $dateField) {
                if (
                    isset($validatedData['custom_rules'][$dateField]['from_year'], $validatedData['custom_rules'][$dateField]['to_year'])
                    && $validatedData['custom_rules'][$dateField]['from_year']
                    && $validatedData['custom_rules'][$dateField]['to_year']
                ) {
                    $validatedData['custom_rules'][$dateField]['from_year'] = Carbon::parse($validatedData['custom_rules'][$dateField]['from_year'])->startOfYear()->toDateString();
                    $validatedData['custom_rules'][$dateField]['to_year'] = Carbon::parse($validatedData['custom_rules'][$dateField]['to_year'])->endOfYear()->toDateString();
                }
            }
        }

        Group::query()->updateOrCreate(
            ['id' => $this->form->group_id],
            $validatedData
        );

        $this->success($this->form->group_id ? __('Group updated.') : __('Group created.'));

        $this->dispatch('refresh-parent');
        $this->dispatch('reset-custom-rules');

        $this->form->reset();
    }

    public function calculateGroupSize(): void
    {
        $validatedData = $this->form->validate();

        $consumer = Consumer::query()
            ->when($this->user->hasRole(Role::CREDITOR), function (Builder $query): void {
                $query->where('company_id', $this->user->company_id);
            })
            ->selectRaw('id, COUNT(*) as total_count, SUM(current_balance) as total_balance')
            ->where(function (Builder $query) use ($validatedData): void {
                GroupConsumerState::tryFrom($validatedData['consumer_state'])->getBuilder($query);

                if ($validatedData['custom_rules'] ?? false) {
                    foreach ($validatedData['custom_rules'] as $rule => $data) {
                        $groupCustomRule = GroupCustomRules::tryFrom($rule);
                        if (in_array($groupCustomRule, [
                            GroupCustomRules::DATE_OF_BIRTH,
                            GroupCustomRules::EXPIRATION_DATE,
                        ])) {
                            $data = [
                                Carbon::createFromFormat('Y', head($data))->startOfYear(),
                                Carbon::createFromFormat('Y', last($data))->endOfYear(),
                            ];
                        }
                        $groupCustomRule->getBuilder($query, $data);
                    }
                }
            })
            ->first();

        $this->groupSize = $consumer->getAttribute('total_count');
        $this->totalBalance = Number::currency((float) $consumer->total_balance);

        $this->openModal = true;
    }

    public function edit(Group $group): void
    {
        if ($group->company_id !== $this->user->company_id && $this->user->id !== $group->user_id) {
            $this->error(__('You do not have permission to edit this group.'));

            return;
        }

        $this->form->init($group);

        $this->dispatch('update-custom-rules');

        $this->dispatch('close-menu');
    }

    public function render(): View
    {
        return view('livewire.creditor.communications.group.index-page')
            ->title(__('Group Management'));
    }
}
