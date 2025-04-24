<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\ImportConsumersViaSFTPCommand;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\Role as EnumsRole;
use App\Jobs\GenerateErrorFileOfImportedConsumersViaSFTPJob;
use App\Jobs\ImportConsumersJob;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Membership;
use App\Models\Merchant;
use App\Models\SftpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportConsumersViaSFTPCommandTest extends TestCase
{
    #[Test]
    public function it_can_dispatch_import_consumer_job(): void
    {
        Bus::fake();
        $disk = Storage::fake();

        Storage::shouldReceive('createSftpDriver')
            ->with([
                'host' => $host = 'eu-central-1.sftpcloud.io',
                'username' => $username = '9dc0e3aada74463ca760ba78a184c644',
                'password' => $password = 'w2QGQeSDOi1cm82O9GRmUKsHLbPxANCd',
                'port' => $port = 22,
                'timeout' => 360,
            ])
            ->andReturn($disk);

        Storage::shouldReceive('exists')->withAnyArgs()->once()->andReturnFalse();
        Storage::shouldReceive('put')->withAnyArgs()->once();

        $disk->put('import/Header profile/Add New Accounts/xyz.csv', json_encode([]));

        $company = Company::factory()->create([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        CompanyMembership::factory()
            ->for(Membership::factory()->state(['upload_accounts_limit' => 10]))
            ->for($company)
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => now()->addMonth(),
            ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::TERMS_AND_CONDITIONS],
                ['type' => CustomContentType::ABOUT_US]
            )
            ->create([
                'company_id' => $company->id,
                'subclient_id' => null,
            ]);

        $user = User::factory()
            ->for($company)
            ->create([
                'subclient_id' => null,
                'parent_id' => null,
                'blocked_at' => null,
                'blocker_user_id' => null,
            ]);

        $user->assignRole(Role::query()->create(['name' => EnumsRole::CREDITOR]));

        $sftpConnection = SftpConnection::factory()
            ->create([
                'company_id' => $company->id,
                'name' => 'Test sftp connection',
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'enabled' => true,
                'export_filepath' => null,
                'import_filepath' => 'import',
            ]);

        Merchant::factory()
            ->create([
                'company_id' => $company->id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::factory()
            ->for($sftpConnection)
            ->for($company)
            ->create([
                'name' => 'Header profile',
                'is_mapped' => true,
            ]);

        $this->artisan(ImportConsumersViaSFTPCommand::class)->assertOk();

        Bus::assertChained([
            ImportConsumersJob::class,
            GenerateErrorFileOfImportedConsumersViaSFTPJob::class,
        ]);
    }
}
