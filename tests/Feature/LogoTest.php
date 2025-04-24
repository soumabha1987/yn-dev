<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Creditor\Logo;
use App\Models\PersonalizedLogo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class LogoTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_livewire_page(): void
    {
        Cache::shouldReceive('remember')->never();

        Auth::logout();

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.creditor.logo')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_logo_of_the_current_logged_in_user_company(): void
    {
        $personalizedLogo = PersonalizedLogo::query()->create([
            'company_id' => $this->user->company_id,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'customer_communication_link' => fake()->word(),
        ]);

        Livewire::test(Logo::class)
            ->assertViewIs('livewire.creditor.logo')
            ->assertOk();

        $this->assertEquals(Cache::get("personalized-logo-{$this->user->id}")->id, $personalizedLogo->id);
    }

    #[Test]
    public function if_user_is_logged_in_but_they_dont_have_personalized_logo(): void
    {
        Livewire::test(Logo::class)
            ->assertViewIs('livewire.creditor.logo')
            ->assertOk();

        $this->assertEquals(Cache::get("personalized-logo-{$this->user->id}")->primary_color, '#3279be');
        $this->assertEquals(Cache::get("personalized-logo-{$this->user->id}")->secondary_color, '#000000');
    }
}
