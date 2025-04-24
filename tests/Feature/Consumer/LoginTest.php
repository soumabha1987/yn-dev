<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\Login;
use App\Models\Consumer;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component_when_visit_the_route(): void
    {
        $this->withoutVite()
            ->get(route('consumer.login'))
            ->assertSeeLivewire(Login::class)
            ->assertOk();

        $this->assertGuest();
    }

    #[Test]
    public function it_can_not_authenticate_with_wrong_credentials(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Consumer::factory()->create([
            'company_id' => null,
            'subclient_id' => null,
            'consumer_profile_id' => null,
            'last_name' => fake()->lastName(),
            'dob' => '1999-12-12',
            'last4ssn' => fake()->randomNumber(4, true),
        ]);

        Livewire::test(Login::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_name', 'consumer')
            ->set('form.dob', '1921-12-12')
            ->set('form.last_four_ssn', '1223')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.last_name' => [__('auth.failed')]])
            ->assertOk();

        Notification::assertNotNotified(__('Logged in.'));
    }

    #[Test]
    public function it_can_authenticate_a_consumer(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Cache::shouldReceive('forget')->once()->with('personalized-logo')->andReturnTrue();

        $consumer = Consumer::factory()->create([
            'company_id' => null,
            'subclient_id' => null,
            'consumer_profile_id' => null,
            'last_name' => $lastName = fake()->lastName(),
            'dob' => $dob = '1999-12-12',
            'last4ssn' => $ssn = fake()->randomNumber(4, true),
            'status' => fake()->randomElement(ConsumerStatus::notVerified()),
        ]);

        Livewire::test(Login::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_name', $lastName)
            ->set('form.dob', $dob)
            ->set('form.last_four_ssn', $ssn)
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors()
            ->assertRedirect(route('consumer.account'));

        $this->assertAuthenticatedAs($consumer, 'consumer');

        Notification::assertNotified(__('Logged in.'));

        $this->assertEquals(ConsumerStatus::JOINED, $consumer->refresh()->status);
    }

    #[Test]
    #[DataProvider('lastNameVariations')]
    public function it_can_authenticate_with_last_name_variations(string $lastName): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        $consumer = Consumer::factory()->create([
            'company_id' => null,
            'subclient_id' => null,
            'consumer_profile_id' => null,
            'last_name' => 'test user',
            'dob' => $dob = '1999-12-12',
            'last4ssn' => $ssn = fake()->randomNumber(4, true),
            'status' => fake()->randomElement(ConsumerStatus::notVerified()),
        ]);

        Livewire::test(Login::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_name', $lastName)
            ->set('form.dob', $dob)
            ->set('form.last_four_ssn', $ssn)
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors()
            ->assertRedirect(route('consumer.account'));

        $this->assertAuthenticatedAs($consumer, 'consumer');

        Notification::assertNotified(__('Logged in.'));

        $this->assertEquals(ConsumerStatus::JOINED, $consumer->refresh()->status);
    }

    #[Test]
    public function it_can_returns_required_validation(): void
    {
        Livewire::test(Login::class)
            ->assertSet('resetCaptcha', false)
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors([
                'form.last_name' => ['required'],
                'form.dob' => ['required'],
                'form.last_four_ssn' => ['required'],
                'form.recaptcha' => ['required'],
            ])
            ->assertOk();

        Notification::assertNotNotified(__('Logged in.'));
    }

    #[Test]
    public function it_can_throw_other_validations(): void
    {
        Livewire::test(Login::class)
            ->set('form.last_name', Str::repeat('test name', 51))
            ->set('form.dob', 'john@johndo.com')
            ->set('form.last_four_ssn', 'consumer')
            ->call('authenticate')
            ->assertHasErrors([
                'form.last_name' => ['max'],
                'form.dob' => ['date'],
                'form.last_four_ssn' => ['numeric'],
                'form.recaptcha' => ['required'],
            ])
            ->assertOk();

        Notification::assertNotNotified(__('Logged in.'));
    }

    #[Test]
    public function it_can_check_validation(): void
    {
        Livewire::test(Login::class)
            ->set('form.last_name', fake()->lastName())
            ->set('form.dob', now())
            ->set('form.last_four_ssn', '1234567890')
            ->set('form.recaptcha', 'invalid-recaptcha-token')
            ->call('authenticate')
            ->assertHasErrors([
                'form.dob' => ['date_format'],
                'form.last_four_ssn' => ['digits'],
            ])
            ->assertOk();

        Notification::assertNotNotified(__('Logged in.'));
    }

    #[Test]
    public function it_can_throw_futures_dob_validation(): void
    {
        Livewire::test(Login::class)
            ->set('form.last_name', fake()->lastName())
            ->set('form.dob', now()->toDateString())
            ->set('form.last_four_ssn', '1234567890')
            ->set('form.recaptcha', 'invalid-recaptcha-token')
            ->call('authenticate')
            ->assertHasErrors([
                'form.dob' => ['before:today'],
                'form.last_four_ssn' => ['digits'],
            ])
            ->assertOk();

        Notification::assertNotNotified(__('Logged in.'));
    }

    #[Test]
    public function it_can_throw_rate_limiting_error(): void
    {
        $this->travelTo(now()->addMinutes(10));

        Http::fake(fn () => Http::response(['success' => true]));

        Livewire::test(Login::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_name', 'consumer')
            ->set('form.dob', '1789-12-11')
            ->set('form.last_four_ssn', '3891')
            ->set('form.recaptcha', fake()->uuid())
            ->call('authenticate')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors('form.last_name')
            ->tap(function (Testable $test): void {
                collect(range(1, 5))->each(function (int $index) use ($test) {
                    $test->call('authenticate');
                    if ($index === 5) {
                        $test->assertHasErrors(['form.last_name' => [__('auth.throttle', [
                            'seconds' => 60,
                            'minutes' => ceil(60 / 60),
                        ])]]);
                    }
                });
            })
            ->assertOk();
    }

    public static function lastNameVariations(): array
    {
        return [
            ['T E S T U S E R'],
            ['test user'],
            ['TestUser'],
            ['TesT UseR'],
            ['testuser'],
        ];
    }
}
