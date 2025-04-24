<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Profile;

use App\Livewire\Consumer\Forms\Profile\CommunicationControlsForm;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class CommunicationControls extends Component
{
    public CommunicationControlsForm $form;

    public bool $isCommunicationUpdated = false;

    private Consumer $consumer;

    public function __construct()
    {
        $this->consumer = Auth::guard('consumer')->user();
        $this->consumer->loadMissing('consumerProfile');
    }

    public function mount(): void
    {
        if ($this->consumer->consumerProfile) {
            $this->form->init($this->consumer->consumerProfile);
        }

        $this->isCommunicationUpdated = ! $this->consumer->consumerProfile->is_communication_updated;
    }

    public function updateTextPermission(): void
    {
        if ($this->form->text_permission === false && $this->form->email_permission === false) {
            $this->form->text_permission = $this->consumer->consumerProfile?->text_permission ?? false;

            $this->error(__('In order to communicate, either email or text permission must be enabled.'));

            return;
        }

        $this->consumer->consumerProfile()->update(['text_permission' => DB::raw('NOT text_permission')]);

        $this->updateCommunication($this->consumer->consumerProfile);

        $this->dispatch(Str::kebab(__FUNCTION__));
    }

    public function updateMobile(): void
    {
        $this->form->mobile = Str::replace(['-', '(', ')', ' '], '', $this->form->mobile);

        $validatedData = $this->form->validate(['mobile' => ['required', 'numeric', 'phone:US']]);

        if ($validatedData['mobile'] === $this->consumer->consumerProfile->mobile) {
            return;
        }

        $this->consumer->consumerProfile()->update($validatedData);

        $this->updateCommunication($this->consumer->consumerProfile);

        $this->success(__('Mobile number updated successfully.'));
    }

    public function updateEmailPermission(): void
    {
        if ($this->form->text_permission === false && $this->form->email_permission === false) {
            $this->form->email_permission = $this->consumer->consumerProfile?->email_permission ?? false;

            $this->error(__('In order to communicate, either email or text permission must be enabled.'));

            return;
        }

        $this->consumer->consumerProfile()->update(['email_permission' => DB::raw('NOT email_permission')]);

        $this->updateCommunication($this->consumer->consumerProfile);

        if ($this->consumer->consumerProfile->refresh()->email_permission) {
            ConsumerUnsubscribe::query()
                ->where('consumer_id', $this->consumer->id)
                ->where('company_id', $this->consumer->company_id)
                ->update([
                    'email' => null,
                ]);
        }

        $this->dispatch(Str::kebab(__FUNCTION__));
    }

    public function updateEmail(): void
    {
        $validatedData = $this->form->validate(['email' => ['required', 'string', 'email']]);

        if ($validatedData['email'] === $this->consumer->consumerProfile->email) {
            return;
        }

        $this->consumer->consumerProfile()->update($validatedData);

        $this->updateCommunication($this->consumer->consumerProfile);

        $this->success(__('Email updated successfully.'));
    }

    public function confirmCommunicationSettings(): void
    {
        $this->updateCommunication($this->consumer->consumerProfile);

        $this->redirectRoute('consumer.account', navigate: true);

        $this->success(__('You have successfully updated communications.'));
    }

    private function updateCommunication(ConsumerProfile $consumerProfile): void
    {
        if (! $consumerProfile->is_communication_updated) {
            $consumerProfile->update(['is_communication_updated' => true]);
        }
    }

    public function render(): View
    {
        return view('livewire.consumer.profile.communication-controls');
    }
}
