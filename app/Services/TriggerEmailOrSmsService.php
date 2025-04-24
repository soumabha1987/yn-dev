<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminConfigurationSlug;
use App\Enums\AutomatedTemplateType;
use App\Enums\TemplateCustomField;
use App\Mail\AutomatedTemplateMail;
use App\Models\AdminConfiguration;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\Consumer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TriggerEmailOrSmsService
{
    /**
     * @param  array<string, mixed>  $checkSendSMSOrEmail
     * @return array{
     *  automated_template: ?AutomatedTemplate,
     *  cost: ?float,
     *  to: ?string
     * }
     */
    public function send(Consumer $consumer, CommunicationStatus $communicationStatus, array $checkSendSMSOrEmail): array
    {
        ['type' => $type, 'to' => $to] = $checkSendSMSOrEmail;

        if ($type === AutomatedTemplateType::EMAIL) {
            $this->sendEmail($consumer, $to, $communicationStatus->emailTemplate->subject, $communicationStatus->emailTemplate->content);

            return [
                'automated_template' => $communicationStatus->emailTemplate,
                'cost' => AdminConfiguration::query()->where('slug', AdminConfigurationSlug::EMAIL_RATE)->value('value'),
                'to' => $to,
            ];
        }

        $response = $this->sendSMS($consumer, (string) $to, $communicationStatus->smsTemplate->content);

        Log::channel('daily')->info('Telnyx response', [
            'response status code' => $response->status(),
            'response body' => $response->body(),
            'consumer_id' => $consumer->id,
            'phone' => $to,
            'automated_template_id' => $communicationStatus->id,
        ]);

        if ($response->ok()) {
            return [
                'automated_template' => $communicationStatus->smsTemplate,
                'cost' => $response->json('data.cost.amount'),
                'to' => $to,
            ];
        }

        Log::channel('daily')->error('Something went wrong in telnyx', [
            'response status code' => $response->status(),
            'response body' => $response->body(),
            'consumer_id' => $consumer->id,
            'phone' => $to,
            'automated_template_id' => $communicationStatus->id,
        ]);

        return [
            'automated_template' => null,
            'to' => null,
            'cost' => null,
        ];
    }

    public function sendEmail(Consumer $consumer, string $to, string $subjectOfTemplate, string $contentOfTemplate): void
    {
        $subject = TemplateCustomField::swapContent($consumer, $subjectOfTemplate);
        $content = TemplateCustomField::swapContent($consumer, $contentOfTemplate);

        Mail::to($to)->send(new AutomatedTemplateMail($consumer, $subject, $content));
    }

    public function sendSMS(Consumer $consumer, string $to, string $contentOfTemplate): Response
    {
        $message = TemplateCustomField::swapContent($consumer, $contentOfTemplate);

        if (App::isLocal()) {
            Http::fake(fn () => Http::response([
                'data' => [
                    'cost' => [
                        'amount' => 2,
                    ],
                ],
            ]));
        }

        return Http::telnyx()->post('/messages', [
            'from' => config('services.telnyx.from'),
            'to' => app(TelnyxService::class)->phoneNumberFormatter($to),
            'text' => $message,
        ]);
    }
}
