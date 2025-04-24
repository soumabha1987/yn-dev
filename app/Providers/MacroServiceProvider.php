<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Timezone;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component;
use RicorocksDigitalAgency\Soap\Facades\Soap;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $variants = [
            'error' => 'danger',
            'success' => 'success',
            'info' => 'info',
            'warning' => 'warning',
        ];

        foreach ($variants as $variant => $method) {
            Component::macro($variant, function (string $message, int $duration = 10000) use ($variant, $method): void {
                Notification::make($variant . '-' . Str::orderedUuid())
                    ->title($message)
                    ->duration($duration)
                    ->{$method}()
                    ->send();
            });
        }

        Builder::macro('search', fn ($name, $search) => $this->where($name, 'LIKE', $search ? '%' . $search . '%' : ''));
        Builder::macro('orSearch', fn ($name, $search) => $this->orWhere($name, 'LIKE', $search ? '%' . $search . '%' : ''));

        Http::macro('telnyx', function (): PendingRequest {
            return Http::withHeader('Content-Type', 'application/json')
                ->acceptJson()
                ->withToken(config('services.telnyx.token'))
                ->baseUrl('https://api.telnyx.com/v2');
        });

        Http::macro('tilled', function (int|string $tilledMerchantId): PendingRequest {
            $baseUrl = config('services.merchant.tilled_sandbox_enabled')
                ? 'https://sandbox-api.tilled.com/v1'
                : 'https://api.tilled.com/v1';

            return Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.merchant.tilled_api_key'),
                'tilled-account' => $tilledMerchantId,
            ])->baseUrl($baseUrl);
        });

        Soap::macro('usaepay', function (string $sourceKey, string $pin) {
            $wsdl = config('services.usaepay_url');

            $seed = time() . rand();

            $clear = $sourceKey . $seed . $pin;

            $hash = sha1($clear);

            $token = [
                'SourceKey' => $sourceKey,
                'PinHash' => [
                    'Type' => 'sha1',
                    'Seed' => $seed,
                    'HashValue' => $hash,
                ],
                'ClientIP' => request()->ip(),
            ];

            Soap::include($token)->for($wsdl);

            return Soap::to($wsdl);
        });

        Carbon::macro('addBimonthly', fn (int $iterations = 1): Carbon => $this->addWeeks(2 * $iterations)); // @phpstan-ignore-line

        Collection::macro('putAfter', function (array $newData, string $key, bool $isValue = false): Collection {
            /** @var Collection $this */
            $index = $this->when(! $isValue, fn ($collection) => $collection->keys())->search($key);

            if ($index === false) {
                return $this;
            }

            if ($isValue) {
                return $this->slice(0, $index + 1)
                    ->merge($newData)
                    ->merge($this->slice($index + 1))
                    ->values();
            }

            return $this->splice($index + 1, 0, $newData)->values();
        });

        Carbon::macro('formatWithTimezone', function (?string $timezone = null, string $format = 'M d, Y'): string {
            $timezone = $timezone ?? (Auth::check() && Auth::user()->company ? Auth::user()->company->timezone->value : Timezone::EST->value);

            return $this->setTimezone($timezone)->format($format); // @phpstan-ignore-line
        });

        Str::macro('formatPhoneNumber', fn (string $phoneNumber) => preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $phoneNumber));
    }
}
