<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\VideoTutorialPage;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VideoTutorialPageTest extends TestCase
{
    #[Test]
    public function it_can_render_video_tutorial_page(): void
    {
        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $user = User::factory()
            ->for(
                Company::factory()
                    ->hasAttached(Membership::factory(), [
                        'auto_renew' => true,
                        'current_plan_end' => now()->addMonthNoOverflow(),
                    ])
                    ->state(['current_step' => CreditorCurrentStep::COMPLETED])
            )
            ->create();
        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('creditor.video-tutorial'))
            ->assertSeeLivewire(VideoTutorialPage::class)
            ->assertOk();
    }
}
