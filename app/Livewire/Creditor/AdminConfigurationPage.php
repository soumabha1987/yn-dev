<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Models\AdminConfiguration;
use App\Models\FeatureFlag;
use App\Services\AdminConfigurationService;
use App\Services\FeatureFlagService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class AdminConfigurationPage extends Component
{
    public array $ids = [];

    public Collection $adminConfigurations;

    public array $adminConfigurationValues = [];

    public function mount(): void
    {
        $this->adminConfigurations = app(AdminConfigurationService::class)->fetch();
        $this->adminConfigurationValues = $this->adminConfigurations->pluck('value', 'id')->all();
    }

    public function updateStatus(FeatureFlag $featureFlag): void
    {
        $featureFlag->update(['status' => ! $featureFlag->status]);

        $this->dispatch("updated-status-$featureFlag->id", $featureFlag->status ? __('Activated') : __('Deactivated'));
    }

    #[Renderless]
    public function updateConfiguration(AdminConfiguration $adminConfiguration): void
    {
        $validator = Validator::make(
            [$adminConfiguration->slug->displayName() => $this->adminConfigurationValues[$adminConfiguration->id]],
            [$adminConfiguration->slug->displayName() => $adminConfiguration->slug->validate()]
        );

        if ($validator->fails()) {
            // Reset the values because we dont call the render method!
            $this->adminConfigurationValues = app(AdminConfigurationService::class)->fetch()->pluck('value', 'id')->all();

            $this->error($validator->errors()->first());

            return;
        }

        $adminConfiguration->update(['value' => $this->adminConfigurationValues[$adminConfiguration->id]]);

        $this->success(__('Configuration updated.'));
    }

    public function render(): View
    {
        $featureFlags = app(FeatureFlagService::class)->fetch();

        $this->ids = $featureFlags->where('status', true)->pluck('id')->toArray();

        return view('livewire.creditor.admin-configuration-page')
            ->with('featureFlags', $featureFlags)
            ->title(__('Admin Configuration'));
    }
}
