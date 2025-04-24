<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class VideoTutorialPage extends Component
{
    public function render(): View
    {
        return view('livewire.creditor.video-tutorial-page')
            ->title(__('Video Tutorial'));
    }
}
