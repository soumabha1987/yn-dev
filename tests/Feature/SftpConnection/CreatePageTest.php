<?php

declare(strict_types=1);

namespace Tests\Feature\SftpConnection;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\SftpConnection\CreatePage;
use App\Models\SftpConnection;
use App\Services\SftpService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class CreatePageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->company()->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);
    }

    #[Test]
    public function it_can_render_create_page_of_sftp_connection(): void
    {
        $this->get(route('creditor.sftp.create'))
            ->assertOk()
            ->assertSee(__('Create SFTP Connection'))
            ->assertSeeLivewire(CreatePage::class);
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(CreatePage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.create-page')
            ->assertSet('form.port', 22);
    }

    #[Test]
    public function it_can_save_the_sftp_connection_with_validate(): void
    {
        $disk = Storage::fake();

        Storage::shouldReceive('createSftpDriver')->with([
            'host' => $host = '127.0.0.1',
            'username' => $username = fake()->userName(),
            'password' => $password = fake()->password(),
            'port' => $port = 3306,
            'timeout' => 360,
        ])->andReturn($disk);

        Livewire::test(CreatePage::class)
            ->assertOk()
            ->assertSet('form.used_for', 'export')
            ->assertSet('form.port', 22)
            ->set('form.name', 'Test connection')
            ->set('form.host', $host)
            ->set('form.port', $port)
            ->set('form.username', $username)
            ->set('form.password', $password)
            ->set('form.used_for', 'import')
            ->set('form.import_filepath', 'test/test-another/import/')
            ->call('save')
            ->assertRedirectToRoute('creditor.sftp');

        Notification::assertNotified(__('SFTP connected.'));

        $this->assertDatabaseCount(SftpConnection::class, 1)
            ->assertDatabaseHas(SftpConnection::class, [
                'company_id' => $this->company->id,
                'name' => 'Test connection',
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
                'key_filepath' => null,
                'export_filepath' => null,
                'import_filepath' => 'test/test-another/import/',
            ]);

        $disk->assertMissing('test/test-another/import/' . SftpService::FAKE_FILE_NAME);
    }
}
