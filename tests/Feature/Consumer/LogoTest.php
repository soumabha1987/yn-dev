<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer;

use App\Livewire\Consumer\Logo;
use App\Models\Consumer;
use App\Models\ConsumerPersonalizedLogo;
use App\Models\PersonalizedLogo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_page(): void
    {
        Cache::shouldReceive('remember')->never();

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.consumer.logo')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_logo_of_the_current_logged_in_user_company_personalized_logo(): void
    {
        $consumer = Consumer::factory()->create(['subclient_id' => null]);

        $personalizedLogo = PersonalizedLogo::factory()
            ->for($consumer->company)
            ->create(['subclient_id' => null]);

        Auth::guard('consumer')->login($consumer);

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.consumer.logo')
            ->assertOk();

        $cachedLogo = Cache::get("personalized-logo-{$consumer->id}");

        $this->assertEquals($personalizedLogo->id, $cachedLogo->id);
        $this->assertEquals($personalizedLogo->primary_color, $cachedLogo->primary_color);
        $this->assertEquals($personalizedLogo->secondary_color, $cachedLogo->secondary_color);
    }

    #[Test]
    public function it_can_render_logo_of_the_current_logged_in_user_subclient_personalized_logo(): void
    {
        $consumer = Consumer::factory()->create();

        $personalizedLogo = PersonalizedLogo::factory()
            ->for($consumer->company)
            ->for($consumer->subclient)
            ->create();

        Auth::guard('consumer')->login($consumer);

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.consumer.logo')
            ->assertOk();

        $cachedLogo = Cache::get("personalized-logo-{$consumer->id}");

        $this->assertEquals($personalizedLogo->id, $cachedLogo->id);
        $this->assertEquals($personalizedLogo->primary_color, $cachedLogo->primary_color);
        $this->assertEquals($personalizedLogo->secondary_color, $cachedLogo->secondary_color);
    }

    #[Test]
    public function it_can_render_current_consumer_personalized_logo(): void
    {
        $consumer = Consumer::factory()
            ->has(ConsumerPersonalizedLogo::factory())
            ->create();

        Auth::guard('consumer')->login($consumer);

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.consumer.logo')
            ->assertOk();

        $cachedLogo = Cache::get("personalized-logo-{$consumer->id}");

        $this->assertEquals($consumer->consumerPersonalizedLogo->id, $cachedLogo->id);
        $this->assertEquals($consumer->consumerPersonalizedLogo->primary_color, $cachedLogo->primary_color);
        $this->assertEquals($consumer->consumerPersonalizedLogo->secondary_color, $cachedLogo->secondary_color);
    }

    #[Test]
    public function it_if_user_is_logged_in_but_they_dont_have_personalized_logo(): void
    {
        $consumer = Consumer::factory()->create();

        Auth::guard('consumer')->login($consumer);

        Livewire::actingAs($consumer)
            ->test(Logo::class)
            ->assertViewIs('livewire.consumer.logo')
            ->assertOk();

        $cachedLogo = Cache::get("personalized-logo-{$consumer->id}");
        $this->assertEquals('#3279be', $cachedLogo->primary_color);
        $this->assertEquals('#000000', $cachedLogo->secondary_color);
    }
}
