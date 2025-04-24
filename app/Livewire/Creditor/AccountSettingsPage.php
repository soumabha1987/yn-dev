<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Livewire\Creditor\Forms\AccountSettingsForm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class AccountSettingsPage extends Component
{
    use WithFileUploads;

    public AccountSettingsForm $form;

    public ?UploadedFile $image = null;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $user = $this->user->loadMissing('company');
        $this->form->init($user->company);
    }

    public function updateSettings(): void
    {
        $validatedData = $this->form->validate();

        $validatedData['from_time'] = Carbon::parse($validatedData['from_time'], $validatedData['timezone'])->utc();
        $validatedData['to_time'] = Carbon::parse($validatedData['to_time'], $validatedData['timezone'])->utc();

        $this->updateProfilePicture();

        $this->user->company()->update($validatedData);

        $this->success(__('Account profile updated successfully!'));
    }

    protected function updateProfilePicture(): void
    {
        $validatedData = $this->validate(
            ['image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048']],
            ['image.image' => 'The uploaded file must be an image.']
        );

        if ($validatedData['image']) {
            $imageName = time() . '_' . rand(0000, 9999) . '.' . $validatedData['image']->extension();

            $validatedData['image']->storeAs('public/profile-images', $imageName);

            Storage::delete('public/profile-images/' . $this->user->image);

            $this->reset('image');

            $this->user->update(['image' => $imageName]);

            $this->dispatch('profile-photo-updated', asset('storage/profile-images/' . $imageName));
        }
    }

    public function resetImageValidation(): void
    {
        $this->resetValidation('image');
    }

    public function render(): View
    {
        return view('livewire.creditor.account-settings-page')->title(__('Account Settings'));
    }
}
