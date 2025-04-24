<?php

declare(strict_types=1);

namespace App\Livewire\Creditor;

use App\Services\PersonalizedLogoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Logo extends Component
{
    #[Computed]
    public function personalizedLogo(): mixed
    {
        $personalizedLogo = (object) ['primary_color' => '#3279be', 'secondary_color' => '#000000'];

        if ($user = Auth::user()) {
            return Cache::remember(
                "personalized-logo-{$user->id}",
                now()->addHour(),
                function () use ($user, $personalizedLogo) {
                    return app(PersonalizedLogoService::class)->findBySubclient($user->company_id, $user->subclient_id)
                        ?: app(PersonalizedLogoService::class)->findByCompanyId($user->company_id)
                        ?: $personalizedLogo;
                }
            );
        }

        return $personalizedLogo;
    }

    public function render(): View
    {
        return view('livewire.creditor.logo');
    }
}
