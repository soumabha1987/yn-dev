<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Consumer\MyAccount;
use App\Livewire\Creditor\Dashboard\OpenNegotiations;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticateMiddlewareTest extends TestCase
{
    #[Test]
    public function it_can_render_consumer_guard(): void
    {
        $consumer = Consumer::factory()->create(['status' => ConsumerStatus::JOINED]);

        Auth::guard('consumer')->login($consumer);

        $this->withoutVite()
            ->get(route('consumer.account'))
            ->assertSeeLivewire(MyAccount::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_default_guard_for_creditor(): void
    {
        $user = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $user->assignRole($role);

        $user->company()->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        CompanyMembership::factory()
            ->for($user->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE->value,
                'current_plan_end' => now()->addMonthNoOverflow(),
            ]);

        Auth::login($user);

        $this->withoutVite()
            ->get(route('creditor.dashboard'))
            ->assertSeeLivewire(OpenNegotiations::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_consumer_guard_without_login(): void
    {
        $this->withoutVite()
            ->get(route('consumer.account'))
            ->assertRedirectToRoute('consumer.login');
    }

    #[Test]
    public function it_can_render_default_guard_without_login(): void
    {
        $this->withoutVite()
            ->get(route('creditor.dashboard'))
            ->assertRedirectToRoute('login');
    }
}
