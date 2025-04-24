<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\MembershipInquiries;

use App\Livewire\Creditor\Forms\MembershipInquiryForm;
use App\Mail\SpecialMembershipInquiryMail;
use App\Mail\SuperAdminMembershipInquiryMail;
use App\Models\MembershipInquiry;
use App\Models\User;
use App\Services\MembershipInquiryService;
use App\Services\TelnyxService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Card extends Component
{
    public MembershipInquiryForm $inquiryForm;

    public bool $dialogOpen = false;

    protected UserService $userService;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();

        $this->user->loadMissing('company');

        $this->userService = app(UserService::class);
    }

    public function membershipInquiry(): void
    {
        $validatedData = $this->inquiryForm->validate();
        $validatedData['company_id'] = $this->user->company_id;

        MembershipInquiry::query()->create($validatedData);

        $this->inquiryForm->reset();

        dispatch(function (): void {
            Mail::to($this->user)->send(new SpecialMembershipInquiryMail($this->user->company));
            Mail::to($this->userService->getAllSuperAdminEmails())->send(new SuperAdminMembershipInquiryMail($this->user->company));
            $this->sendSMS();
        })->afterResponse();

        Cache::forget('new_inquires_count');

        $this->reset('dialogOpen');

        $this->success(__('Thank you for your membership inquiry. We will provide an update very soon.'));
    }

    private function sendSMS(): void
    {
        if (App::isLocal()) {
            Http::fake(function () {
                return Http::response(['data' => [
                    'cost' => [
                        'amount' => 2,
                    ],
                ]]);
            });
        }

        $phoneNumbers = $this->userService->getAllSuperAdminPhoneNumbers();

        $responses = Http::pool(function (Pool $pool) use ($phoneNumbers): void {
            foreach ($phoneNumbers as $phoneNumber) {
                $pool->withHeader('Content-Type', 'application/json')
                    ->acceptJson()
                    ->withToken(config('services.telnyx.token'))
                    ->baseUrl('https://api.telnyx.com/v2')
                    ->post('messages', [
                        'from' => config('services.telnyx.from'),
                        'to' => app(TelnyxService::class)->phoneNumberFormatter($phoneNumber),
                        'text' => __('We have received a new membership inquiry from :name', ['name' => $this->user->name]),
                    ]);
            }
        });

        foreach ($responses as $response) {
            Log::channel('daily')->info('Telnyx response', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.creditor.membership-inquiries.card')
            ->with('membershipInquiryCreatedAt', app(MembershipInquiryService::class)->membershipCreatedAt($this->user->company_id));
    }
}
