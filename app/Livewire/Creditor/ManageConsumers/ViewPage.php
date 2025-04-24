<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageConsumers;

use App\Enums\CommunicationCode;
use App\Enums\ConsumerStatus;
use App\Enums\Role;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\Forms\ManageConsumers\ProfileForm;
use App\Livewire\Creditor\Traits\DecodeImageToBase64;
use App\Livewire\Creditor\Traits\ValidateMarkdown;
use App\Mail\AutomatedTemplateMail;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ELetter;
use App\Models\User;
use App\Services\ConsumerNegotiationService;
use App\Services\ConsumerUnsubscribeService;
use App\Services\ScheduleTransactionService;
use App\Services\TelnyxService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class ViewPage extends Component
{
    use CommonFunctionality;
    use DecodeImageToBase64;
    use ValidateMarkdown;

    public Consumer $consumer;

    public ProfileForm $form;

    public string $emailSubject = '';

    public string $emailContent = '';

    public string $smsContent = '';

    public string $eLetterSubject = '';

    public string $eLetterContent = '';

    public bool $isSuperAdmin = false;

    public bool $text_permission = false;

    public bool $email_permission = false;

    public ?ConsumerNegotiation $consumerNegotiation;

    private User $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function mount(): void
    {
        $this->consumer->loadMissing('company');

        abort_if(
            $this->consumer->company === null,
            Response::HTTP_FORBIDDEN,
            __('Unauthorized')
        );

        abort_if(
            $this->user->hasRole(Role::CREDITOR) && $this->consumer->company_id !== $this->user->company_id,
            Response::HTTP_FORBIDDEN,
            __('Unauthorized')
        );

        $this->consumer->loadMissing([
            'reason:id,label',
            'company:id,company_name',
            'subclient:id,subclient_name',
            'unsubscribe',
            'consumerProfile',
        ]);

        $this->form->setData($this->consumer);

        $this->text_permission = $this->consumer->consumerProfile->text_permission;
        $this->email_permission = $this->consumer->consumerProfile->email_permission;

        $this->isSuperAdmin = $this->user->hasRole(Role::SUPERADMIN);
        $this->consumerNegotiation = app(ConsumerNegotiationService::class)->fetchActiveNegotiationOfConsumer($this->consumer->id);
    }

    public function updateTextPermission(): void
    {
        $this->consumer->consumerProfile()->update(['text_permission' => DB::raw('NOT text_permission')]);

        $this->dispatch(Str::kebab(__FUNCTION__));
    }

    public function updateEmailPermission(): void
    {
        $this->consumer->consumerProfile()->update(['email_permission' => DB::raw('NOT email_permission')]);

        $this->dispatch(Str::kebab(__FUNCTION__));
    }

    public function delete(): void
    {
        if ($this->consumer->status === ConsumerStatus::DEACTIVATED) {
            $this->error(__('This consumer already deactivated can not delete again'));

            $this->dispatch('close-confirmation-box');

            return;
        }

        $this->consumer->update([
            'status' => ConsumerStatus::DEACTIVATED,
            'disputed_at' => now(),
            'reason_id' => null,
            'restart_date' => null,
            'hold_reason' => null,
        ]);

        app(ScheduleTransactionService::class)->markScheduledAndFailedAsCancelled($this->consumer->id);

        app(ConsumerUnsubscribeService::class)->create($this->consumer);

        try {
            TriggerEmailAndSmsServiceJob::dispatch($this->consumer, CommunicationCode::CREDITOR_REMOVED_ACCOUNT);

            $this->success(__('Consumer is deactivated.'));
        } catch (Exception $exception) {
            Log::channel('daily')->error('When consumer is deleted at that time automated communication email', [
                'message' => $exception->getMessage(),
                'consumer_id' => $this->consumer->id,
                'stack trace' => $exception->getTrace(),
            ]);

            $this->error(__('Something went wrong on sending mail.'));
        }

        $this->dispatch('refresh-parent');
    }

    public function updateConsumer(): void
    {
        if ($this->consumer->status === ConsumerStatus::SETTLED) {
            $this->error(__('This account is settled in full with a zero balance due. Accounts cannot be edited after settled.'));

            return;
        }

        if ($this->consumer->status === ConsumerStatus::DEACTIVATED) {
            $this->error(__('This consumer deactivated can not editable'));

            return;
        }

        $validatedData = $this->form->validate();

        if (
            $this->consumer->status === ConsumerStatus::UPLOADED
            && $this->consumer->mobile1 === $this->consumer->consumerProfile->mobile
            && $this->consumer->email1 === $this->consumer->consumerProfile->email
        ) {
            $this->consumer->consumerProfile()->update([
                'email' => $validatedData['email'],
                'mobile' => $validatedData['mobile'],
            ]);
        }

        $this->consumer->update([
            'email1' => $validatedData['email'],
            'mobile1' => $validatedData['mobile'],
        ]);

        $this->success(__('Consumer updated successfully.'));
    }

    public function sendEmail(): void
    {
        if (! $this->consumer->consumerProfile->email_permission) {
            $this->error(__('This consumer has disabled email permission'));

            return;
        }

        if (! $this->consumer->email1) {
            $this->error(__('This consumer does not have an email address.'));

            $this->dispatch('close-dialog');

            return;
        }

        if ($this->consumer->status === ConsumerStatus::DEACTIVATED) {
            $this->error(__('This consumer deactivated so can not send email'));

            $this->dispatch('close-dialog');

            return;
        }

        $validatedData = $this->validate([
            'emailSubject' => ['required', 'string'],
            'emailContent' => ['required', 'string'],
        ]);

        $this->validateContent($validatedData['emailContent'], 'emailContent');

        $content = $this->decodeImageToBase64($validatedData['emailContent'], 'automated-templates');

        // TODO: May be we need to send this mail directly not pushing into queue..
        Mail::to($this->consumer->email1)
            ->send(new AutomatedTemplateMail($this->consumer, $validatedData['emailSubject'], $content));

        // TODO: Add entry in `communication_histories` table after improvements.

        $this->success(__('Email sent.'));

        $this->dispatch('close-dialog');
    }

    public function sendSms(): void
    {
        if (! $this->consumer->consumerProfile->text_permission) {
            $this->error(__('This consumer has disabled text permission'));

            return;
        }

        if (! $this->consumer->mobile1) {
            $this->error(__('This consumer does not have a mobile number.'));

            $this->dispatch('close-dialog');

            return;
        }

        if ($this->consumer->status === ConsumerStatus::DEACTIVATED) {
            $this->error(__('This consumer deactivated so can not send sms'));

            $this->dispatch('close-dialog');

            return;
        }

        $validatedData = $this->validate(['smsContent' => ['required', 'string']]);

        if (App::isLocal()) {
            Http::fake(function () {
                return Http::response(['data' => [
                    'cost' => [
                        'amount' => 2,
                    ],
                ]]);
            });
        }

        $phoneNumber = app(TelnyxService::class)->phoneNumberFormatter($this->consumer->mobile1);

        $response = Http::telnyx()->post('messages', [
            'from' => config('services.telnyx.from'),
            'to' => $phoneNumber,
            'text' => $validatedData['smsContent'],
        ]);

        if ($response->ok()) {
            Log::channel('daily')->info('Telnyx response', [
                'response status code' => $response->status(),
                'response body' => $response->body(),
                'consumer_id' => $this->consumer->id,
                'phone' => $phoneNumber,
            ]);

            $this->success(__('Message sent.'));
        } else {
            Log::channel('daily')->error('Something went wrong in telnyx', [
                'response status code' => $response->status(),
                'response body' => $response->body(),
                'consumer_id' => $this->consumer->id,
                'phone' => $phoneNumber,
            ]);

            $this->error(__('Communications error. We aren\'t able to send your Communication. Please email help@younegotiate.com so we can fix this.'));
        }

        $this->dispatch('close-dialog');
    }

    public function sendELetter(): void
    {
        if (! $this->isSuperAdmin) {
            $this->error(__('You are not allowed to send e-letter.'));

            $this->dispatch('close-dialog');

            return;
        }

        if ($this->consumer->status === ConsumerStatus::DEACTIVATED) {
            $this->error(__('This consumer deactivated so can not send eletter'));

            $this->dispatch('close-dialog');

            return;
        }

        $validatedData = $this->validate([
            'eLetterContent' => ['required', 'string'],
        ]);

        $this->validateContent($validatedData['eLetterContent'], 'eLetterContent');

        $content = $this->decodeImageToBase64($validatedData['eLetterContent'], 'e-letter');

        $eLetter = ELetter::query()
            ->create([
                'company_id' => $this->consumer->company_id,
                'subclient_id' => $this->consumer->subclient_id,
                'message' => $content,
                'disabled' => false,
            ]);

        $eLetter->consumers()->attach($this->consumer->id, ['enabled' => true, 'read_by_consumer' => false]);

        $this->reset('eLetterContent');

        $this->success(__('Secure eLetters delivered.'));

        $this->dispatch('close-dialog');
        $this->dispatch('refresh-parent');
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-consumers.view-page')
            ->title(__('Account Profile'));
    }
}
