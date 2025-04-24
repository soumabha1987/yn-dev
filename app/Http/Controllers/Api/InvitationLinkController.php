<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ConsumerStatus;
use App\Http\Controllers\Controller;
use App\Services\ConsumerService;
use App\Services\EncryptDecryptService;
use App\Services\PersonalizedLogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InvitationLinkController extends Controller
{
    public function __construct(
        private readonly EncryptDecryptService $encryptDecryptService // @phpstan-ignore-line
    ) {}

    public function findAndRedirectConsumer(Request $request): Response
    {
        return redirect('https://consumer.younegotiate.com/login');

        $tag = $this->encryptDecryptService->decrypt($request->input('tag'), config('services.yng.key')); // @phpstan-ignore-line

        if (! $tag) {
            return redirect('https://consumer.younegotiate.com/login')
                ->with(['error' => __('Sorry! we cannot continue using your link.')]);
        }

        $invitationLink = Str::of($tag)
            ->before('/')
            ->prepend(str(config('services.yng.short_link'))->finish('/')->toString())
            ->toString();

        $consumer = app(ConsumerService::class)->findByInvitationLink($invitationLink);

        if (! $consumer) {
            return redirect('https://consumer.younegotiate.com/login')
                ->with(['error' => __('Sorry! we cannot continue using your link.')]);
        }

        if ($consumer->status === ConsumerStatus::UPLOADED) {
            $consumer->update(['status' => ConsumerStatus::VISITED->value]);
        }

        $personalizedLogo = app(PersonalizedLogoService::class)->findByCompanyId($consumer->company_id);

        $subDomain = 'consumer';

        if ($personalizedLogo) {
            $subDomain = $personalizedLogo->customer_communication_link;
        }

        $encryptedConsumerId = $this->encryptDecryptService->encrypt((string) $consumer->id, config('services.yng.key'));

        return response()->json([
            'redirect_url' => $this->encryptDecryptService->encrypt(
                $subDomain . '.younegotiate.com/webview?search=' . $encryptedConsumerId,
                config('services.yng.key'),
            ),
        ]);
    }
}
