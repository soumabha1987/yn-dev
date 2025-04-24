<?php

declare(strict_types=1);

namespace App\Livewire\Consumer;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.consumer.app-layout')]
class Logo extends Component
{
    #[Computed]
    public function personalizedLogo(): mixed
    {
        $personalizedLogo = (object) ['primary_color' => '#3279be', 'secondary_color' => '#000000'];

        if ($consumer = Auth::guard('consumer')->user()) {
            $consumer->loadMissing(['consumerPersonalizedLogo', 'subclient.personalizedLogo', 'company.personalizedLogo']);

            return Cache::remember(
                "personalized-logo-{$consumer->id}",
                now()->addHour(),
                function () use ($consumer, $personalizedLogo) {
                    $consumerPersonalizedLogo = $consumer->consumerPersonalizedLogo
                        ?? $consumer->subclient?->personalizedLogo
                        ?? $consumer->company->personalizedLogo;

                    return $consumerPersonalizedLogo ?: $personalizedLogo;
                }
            );
        }

        return $personalizedLogo;
    }

    public function render(): View
    {
        return view('livewire.consumer.logo');
    }
}
