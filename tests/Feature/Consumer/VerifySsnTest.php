<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Enums\ConsumerStatus;
use App\Livewire\Consumer\VerifySsn;
use App\Models\Consumer;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifySsnTest extends TestCase
{
    protected Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->create([
                'subclient_id' => null,
                'dob' => '2024-05-10',
                'last4ssn' => '1503',
                'last_name' => 'Test Consumer',
                'status' => ConsumerStatus::UPLOADED,
            ]);

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_not_render_livewire_component(): void
    {
        $this->get(route('consumer.verify_ssn'))
            ->assertRedirectToRoute('consumer.account')
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function it_can_render_livewire_component_of_verify_ssn(): void
    {
        Session::put('required_ssn_verification', true);

        Livewire::test(VerifySsn::class)
            ->assertViewIs('livewire.consumer.verify-ssn')
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_error_required_verification(): void
    {
        Session::put('required_ssn_verification', true);

        Livewire::test(VerifySsn::class)
            ->assertSet('resetCaptcha', false)
            ->call('checkSsn')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors([
                'form.last_four_ssn' => 'required',
                'form.recaptcha' => 'required',
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_not_authenticate_when_ssn_is_wrong(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Session::put('required_ssn_verification', true);

        Livewire::test(VerifySsn::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_four_ssn', '3401')
            ->set('form.recaptcha', fake()->uuid())
            ->call('checkSsn')
            ->assertSet('resetCaptcha', true)
            ->assertHasErrors(['form.last_four_ssn'])
            ->assertHasNoErrors(['form.recaptcha'])
            ->assertOk();
    }

    #[Test]
    public function it_can_authenticate_via_verify_ssn(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        Session::put('required_ssn_verification', true);

        Livewire::test(VerifySsn::class)
            ->assertSet('resetCaptcha', false)
            ->set('form.last_four_ssn', '1503')
            ->set('form.recaptcha', fake()->uuid())
            ->call('checkSsn')
            ->assertSet('resetCaptcha', true)
            ->assertHasNoErrors()
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertOk();
    }
}
