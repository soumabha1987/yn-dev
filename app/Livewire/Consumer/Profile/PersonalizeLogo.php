<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Profile;

use App\Livewire\Consumer\Forms\Profile\PersonalizeLogoForm;
use App\Models\Consumer;
use App\Models\ConsumerPersonalizedLogo;
use App\Models\PersonalizedLogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use stdClass;

#[Layout('components.consumer.app-layout')]
class PersonalizeLogo extends Component
{
    use WithFileUploads;

    public PersonalizeLogoForm $form;

    private Consumer $consumer;

    public function __construct()
    {
        $this->consumer = Auth::guard('consumer')->user();
        $this->consumer->loadMissing(['consumerPersonalizedLogo', 'company.personalizedLogo', 'consumerProfile']);
    }

    public function mount(): void
    {
        $personalizedLogo = $this->consumer->consumerPersonalizedLogo
            ?? $this->consumer->subclient?->personalizedLogo
            ?? $this->consumer->company->personalizedLogo;

        if ($personalizedLogo) {
            $this->fillPersonalizedLogo($personalizedLogo);
        }
    }

    public function resetForm(): void
    {
        $personalizedLogo = $this->consumer->subclient?->personalizedLogo ?? $this->consumer->company->personalizedLogo;

        if ($personalizedLogo) {
            $this->fillPersonalizedLogo($personalizedLogo);

            return;
        }

        $this->form->reset();
    }

    public function createOrUpdate(): void
    {
        $validatedData = $this->form->validate();

        $this->storeImage($validatedData);

        ConsumerPersonalizedLogo::query()->updateOrCreate(['consumer_id' => $this->consumer->id], $validatedData);

        Cache::forget("personalized-logo-{$this->consumer->id}");

        $this->dispatch('set-header-logo');

        $this->success(__('Colors updated.'));
    }

    private function fillPersonalizedLogo(PersonalizedLogo|ConsumerPersonalizedLogo $personalizedLogo): void
    {
        $colors = new stdClass;
        $colors->primary_color = $personalizedLogo?->primary_color;
        $colors->secondary_color = $personalizedLogo?->secondary_color;
        $this->form->init($colors);
    }

    /**
     * @param  array{ image: ?UploadedFile }  $data
     */
    private function storeImage(array &$data): void
    {
        /** @var Consumer $consumer */
        $consumer = Auth::user();

        if ($data['image']) {
            Storage::delete('public/profile-image/' . $consumer->consumerProfile?->image);

            $imageName = time() . '-' . rand(0000, 9999) . '.' . $data['image']->extension();

            $data['image']->storeAs('public/profile-images/', $imageName);

            $this->form->reset('image');

            $this->dispatch('profile-photo-updated', Storage::url('profile-images/' . $imageName));

            $this->consumer->consumerProfile()->update(['image' => $imageName]);

            Arr::forget($data, 'image');

            return;
        }

        Arr::forget($data, 'image');
    }

    public function resetFormImageValidation(): void
    {
        $this->resetValidation('form.image');
    }

    public function render(): View
    {
        return view('livewire.consumer.profile.personalize-logo');
    }
}
