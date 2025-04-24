<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Enums\ConsumerStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\Merchant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoftDeleteCompaniesCommandTest extends TestCase
{
    #[Test]
    public function it_can_soft_delete_expired_plan_companies(): void
    {
        CompanyMembership::factory()
            ->for($company = Company::factory()->create(['remove_profile' => true]))
            ->create([
                'current_plan_end' => today()->subDay(),
                'auto_renew' => false,
            ]);

        $this->artisan('delete:plan-expired-companies')->assertOk();

        $this->assertSoftDeleted($company);
    }

    #[Test]
    public function it_can_soft_delete_expired_plan_companies_with_consumers_users_and_more_data(): void
    {
        CompanyMembership::factory()
            ->for($company = Company::factory()->create(['remove_profile' => true]))
            ->create([
                'current_plan_end' => today()->subDay(),
                'auto_renew' => false,
            ]);

        $consumers = Consumer::factory(10)->create([
            'company_id' => $company->id,
            'status' => ConsumerStatus::UPLOADED,
        ]);

        $merchant = Merchant::factory()->create(['company_id' => $company->id]);

        $users = User::factory()
            ->forEachSequence(
                ['email' => $email1 = fake()->safeEmail()],
                ['email' => $email2 = fake()->safeEmail()],
            )
            ->create([
                'company_id' => $company->id,
            ]);

        $this->artisan('delete:plan-expired-companies')->assertOk();

        $this->assertSoftDeleted($company);

        $this->assertDatabaseMissing(Merchant::class, ['id' => $merchant->id]);

        $this->assertDatabaseMissing(Consumer::class, ['status' => ConsumerStatus::UPLOADED]);
        $this->assertEquals(ConsumerStatus::DEACTIVATED, $consumers->first()->refresh()->status);
        $this->assertEquals(ConsumerStatus::DEACTIVATED, $consumers->last()->refresh()->status);
        $this->assertNotEquals($email1, $users->first()->refresh()->email);
        $this->assertNotEquals($email2, $users->last()->refresh()->email);
    }

    #[Test]
    public function it_can_soft_delete_no_any_companies(): void
    {
        CompanyMembership::factory()
            ->for($company = Company::factory()->create(['remove_profile' => true]))
            ->create([
                'current_plan_end' => today()->addDay(),
                'auto_renew' => false,
            ]);

        $this->artisan('delete:plan-expired-companies')->assertOk();

        $this->assertNotSoftDeleted($company);
    }
}
