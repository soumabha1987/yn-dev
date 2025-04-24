<?php

declare(strict_types=1);

namespace Tests\Feature\SftpConnection;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\SftpConnection\EditPage;
use App\Models\SftpConnection;
use App\Services\SftpService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class EditPageTest extends AuthTestCase
{
    protected SftpConnection $sftp;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->company()->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        $this->sftp = SftpConnection::factory()
            ->for($this->company)
            ->create();
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_route(): void
    {
        $this->get(route('creditor.sftp.edit', $this->sftp))
            ->assertOk()
            ->assertSeeLivewire(EditPage::class);
    }

    #[Test]
    public function it_can_redirect_if_the_editable_sftp_connection_is_not_of_logged_in_user(): void
    {
        $sftp = SftpConnection::factory()->create();

        $this->get(route('creditor.sftp.edit', $sftp))
            ->assertRedirectToRoute('creditor.sftp');

        Notification::assertNotified(__('This SFTP URL does not match your membership credentials. Please recreate the URL link from your member account.'));
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        Livewire::test(EditPage::class, ['sftp' => $this->sftp])
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.edit-page')
            ->assertSet('form.name', $this->sftp->name)
            ->assertSet('form.port', $this->sftp->port)
            ->assertSet('form.used_for', 'both')
            ->assertSet('form.export_filepath', $this->sftp->export_filepath);
    }

    #[Test]
    public function it_can_update_without_validating(): void
    {
        Livewire::test(EditPage::class, ['sftp' => $this->sftp])
            ->assertOk()
            ->assertSet('form.used_for', 'both')
            ->assertViewIs('livewire.creditor.sftp-connection.edit-page')
            ->set('form.name', 'Test connection')
            ->set('form.used_for', 'export')
            ->set('form.import_filepath', 'test/import-file/')
            ->set('form.export_filepath', 'test/export-file/')
            ->call('update')
            ->assertRedirectToRoute('creditor.sftp');

        Notification::assertNotified(__('SFTP profile updated.'));

        $this->assertEquals('test/export-file/', $this->sftp->refresh()->export_filepath);
        $this->assertNull($this->sftp->import_filepath);
        $this->assertEquals('Test connection', $this->sftp->name);
    }

    #[Test]
    public function it_can_update_with_validate(): void
    {
        $disk = Storage::fake();

        Storage::shouldReceive('createSftpDriver')->with([
            'host' => $host = '127.0.0.1',
            'username' => $username = fake()->userName(),
            'password' => $password = fake()->password(),
            'port' => $port = 3306,
            'timeout' => 360,
        ])->andReturn($disk);

        Livewire::test(EditPage::class, ['sftp' => $this->sftp])
            ->assertOk()
            ->assertSet('form.used_for', 'both')
            ->assertSet('form.port', $this->sftp->port)
            ->set('form.name', 'Test connection')
            ->set('form.host', $host)
            ->set('form.port', $port)
            ->set('form.username', $username)
            ->set('form.password', $password)
            ->set('form.used_for', 'import')
            ->set('form.import_filepath', 'test/test-another/import/')
            ->call('update')
            ->assertRedirectToRoute('creditor.sftp');

        Notification::assertNotified(__('SFTP profile updated.'));

        $this->assertEquals('test/test-another/import/', $this->sftp->refresh()->import_filepath);
        $this->assertNull($this->sftp->export_filepath);
        $this->assertEquals('Test connection', $this->sftp->name);

        $disk->assertMissing('test/test-another/import/' . SftpService::FAKE_FILE_NAME);
    }
}
