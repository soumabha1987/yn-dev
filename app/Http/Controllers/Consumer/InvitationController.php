<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Consumer;
use App\Services\EncryptDecryptService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->query('search') === null) {
            Notification::make('invitation-error')
                ->title(__('Unauthorized login!'))
                ->danger()
                ->duration(10000)
                ->send();

            return to_route('consumer.login');
        }

        $consumerId = app(EncryptDecryptService::class)
            ->decrypt(
                $request->input('search'),
                config('services.yng.key'),
            );

        $consumer = Consumer::query()->find($consumerId);

        if (! $consumer) {
            Notification::make('invitation-error')
                ->title(__('Unauthorized login!'))
                ->danger()
                ->duration(10000)
                ->send();

            return to_route('consumer.login');
        }

        Auth::guard('consumer')->login($consumer);

        session(['required_ssn_verification' => true]);

        return to_route('consumer.verify_ssn');
    }
}
