<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CompanyMembershipStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class AuthTestCase extends TestCase
{
    protected User $user;

    protected Company $company;

    protected Subclient $subclient;

    protected CompanyMembership $companyMembership;

    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        $this->company = Company::factory()->create();

        $this->subclient = Subclient::factory()
            ->for($this->company)
            ->create();

        $this->user = User::factory()
            ->for($this->company)
            ->for($this->subclient)
            ->create();

        $this->companyMembership = CompanyMembership::factory()
            ->for($this->company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonthNoOverflow(),
            ]);

        $this->withoutVite()
            ->actingAs($this->user);
    }
}
