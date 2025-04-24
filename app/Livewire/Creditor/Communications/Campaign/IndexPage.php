<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Communications\Campaign;

use App\Enums\CampaignFrequency;
use App\Enums\Role;
use App\Enums\TemplateType;
use App\Jobs\ProcessCampaignConsumersJob;
use App\Livewire\Creditor\Forms\Communication\CampaignForm;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\Group;
use App\Models\User;
use App\Services\CompanyMembershipService;
use App\Services\ConsumerService;
use App\Services\EcoLetterPaymentService;
use App\Services\GroupService;
use App\Services\SetupWizardService;
use App\Services\TemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IndexPage extends Component
{
    public CampaignForm $form;

    public float $ecoLetterPrice;

    public ?Group $group;

    public int $groupSize;

    public bool $openCampaignDialog = false;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        if ($this->user->hasRole(Role::CREDITOR) && app(SetupWizardService::class)->getRemainingStepsCount($this->user) !== 0) {

            $this->redirectRoute('creditor.setup-wizard', navigate: true);
        }

        $this->form->start_date = today()->addDay()->toDateString();
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['user_id'] = $this->user->id;
        $validatedData['company_id'] = $this->user->company_id;

        Arr::forget($validatedData, ['type']);

        Campaign::query()->updateOrCreate(
            ['id' => $this->form->campaign_id],
            $validatedData
        );

        $this->dispatch('refresh-list-view');

        $this->success($this->form->campaign_id ? __('This campaign update successfully.') : __('Campaign created.'));

        $this->form->reset();
    }

    public function runCampaignToday(): void
    {
        if (blank($this->form->group_id)) {
            return;
        }

        $this->group = Group::query()->find($this->form->group_id);

        if (blank($this->group)) {
            $this->error(__('This group not exists'));

            return;
        }

        $this->groupSize = app(ConsumerService::class)
            ->countByGroup($this->group, $this->user->company ? $this->user->company_id : null)
            ->getAttribute('total_count');

        if ($this->groupSize === 0) {
            $this->error(__('Sorry, this group has no consumers.'));

            return;
        }

        if (! $this->user->hasRole(Role::CREDITOR)) {
            $this->createImmediately();
        } else {
            $this->ecoLetterPrice = app(CompanyMembershipService::class)
                ->fetchELetterFee($this->user->company_id);

            $this->openCampaignDialog = true;
        }
    }

    public function createImmediately(): void
    {
        $validatedData = $this->form->validate();

        $groupConsumerCount = app(ConsumerService::class)
            ->countByGroup($this->group, $this->user->company ? $this->user->company_id : null);

        $consumersTotalAmount = $groupConsumerCount->getAttribute('total_balance');

        $consumerCount = $groupConsumerCount->getAttribute('total_count');

        if ($consumerCount === 0) {
            $this->error(__('Sorry, this group has no consumers.'));

            $this->reset('openCampaignDialog');

            return;
        }

        if ($this->user->hasRole(Role::CREDITOR)) {
            $isSuccessfullyPayment = app(EcoLetterPaymentService::class)
                ->applyEcoLetterDeduction($this->user->company, $consumerCount);

            if (! $isSuccessfullyPayment) {
                $this->error(__('Sorry, Your payment process failed, Please check payment details.'));

                $this->reset('openCampaignDialog');

                return;
            }
        }

        $campaign = Campaign::query()
            ->updateOrCreate(
                ['id' => $this->form->campaign_id],
                [
                    'user_id' => $this->user->id,
                    'company_id' => $this->user->company_id,
                    'group_id' => $validatedData['group_id'],
                    'template_id' => $validatedData['template_id'],
                    'frequency' => CampaignFrequency::ONCE,
                    'start_date' => today()->toDateString(),
                    'is_run_immediately' => true,
                ]
            );

        $campaignTracker = CampaignTracker::query()
            ->create([
                'campaign_id' => $campaign->id,
                'consumer_count' => $consumerCount,
                'total_balance_of_consumers' => $consumersTotalAmount ?? 0,
            ]);

        ProcessCampaignConsumersJob::dispatch($campaign, $campaignTracker);

        $this->success(__('Your campaign has been successfully created and is now being processed.'));

        $this->dispatch('refresh-list-view');

        $this->form->reset();
    }

    public function edit(Campaign $campaign): void
    {
        if ($campaign->company_id !== $this->user->company_id) {
            $this->error(__('Sorry you can not edit this campaign.'));

            return;
        }

        $this->form->init($campaign);
        $this->reset('openCampaignDialog');
    }

    public function render(): View
    {
        $isCreditor = $this->user->hasRole(Role::CREDITOR);

        $data = [
            'is_creditor' => $isCreditor,
            'company_id' => $this->user->company_id,
        ];

        if ($isCreditor) {
            $eLettersTemplates = app(TemplateService::class)->fetchForCampaignSelectionBox($data)
                ->pluck('name', 'id');
        }

        if (! $isCreditor) {
            $smsTemplates = app(TemplateService::class)->fetchForCampaignSelectionBox($data)
                ->where('type', TemplateType::SMS)
                ->pluck('name', 'id');

            $emailTemplates = app(TemplateService::class)->fetchForCampaignSelectionBox($data)
                ->where('type', TemplateType::EMAIL)
                ->pluck('name', 'id');
        }

        return view('livewire.creditor.communications.campaign.index-page')
            ->with([
                'isCreditor' => $isCreditor,
                'eLettersTemplates' => $eLettersTemplates ?? [],
                'smsTemplates' => $smsTemplates ?? [],
                'emailTemplates' => $emailTemplates ?? [],
                'groups' => app(GroupService::class)->fetchForCampaignSelectionBox($data['company_id']),
            ])
            ->title(__('Schedule Campaigns'));
    }
}
