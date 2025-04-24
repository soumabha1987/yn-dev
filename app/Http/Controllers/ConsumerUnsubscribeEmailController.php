<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsumerUnsubscribeEmailController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            Notification::make('unsubscribe_email')
                ->title(__('Invalid or expired link.'))
                ->danger()
                ->duration(10000)
                ->send();

            return to_route('consumer.login');
        }

        $data = decrypt($request->data);

        ConsumerUnsubscribe::query()
            ->updateOrCreate(
                [
                    'company_id' => $data['company_id'],
                    'consumer_id' => $data['consumer_id'],
                ],
                [
                    'email' => $data['consumer_email'],
                ]
            );

        ConsumerProfile::query()
            ->whereHas('consumers', function ($query) use ($data) {
                $query->where('company_id', $data['company_id'])
                    ->where('id', $data['consumer_id']);
            })
            ->update(['email_permission' => false]);

        Notification::make('unsubscribe_email')
            ->title(__('You have successfully unsubscribed from our emails.'))
            ->success()
            ->duration(10000)
            ->send();

        return to_route('consumer.login');
    }
}
