<?php

declare(strict_types=1);

namespace Tests\Feature\SftpConnection;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\SftpConnection\ListPage;
use App\Models\CsvHeader;
use App\Models\SftpConnection;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListPageTest extends AuthTestCase
{
    protected User $user;

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
    public function it_can_render_sftp_connection_page(): void
    {
        $this->get(route('creditor.sftp'))
            ->assertOk()
            ->assertSeeLivewire(ListPage::class);
    }

    #[Test]
    public function it_can_render_livewire_component_with_data(): void
    {
        $sftpConnection = SftpConnection::factory()
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.list-page')
            ->assertViewHas('sftpConnections', fn (LengthAwarePaginator $sftpConnections) => $sftpConnections->getCollection()->first()->is($sftpConnection));
    }

    #[Test]
    public function it_can_allow_search_by_name(): void
    {
        $createdSftpConnections = SftpConnection::factory()
            ->forEachSequence(
                ['name' => 'First sftp test connection'],
                ['name' => 'Second sftp test connection']
            )
            ->for($this->company)
            ->create();

        Livewire::withQueryParams(['search' => 'Second'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.list-page')
            ->assertViewHas(
                'sftpConnections',
                fn (LengthAwarePaginator $sftpConnections): bool => $sftpConnections->total() === 1 && $sftpConnections->getCollection()->first()->is($createdSftpConnections->last())
            )
            ->assertDontSee(__('No result found'))
            ->assertSee($createdSftpConnections->last()->port)
            ->assertSee($createdSftpConnections->last()->host)
            ->assertSee(__('Both'));
    }

    #[Test]
    public function it_can_allow_search_by_username(): void
    {
        $createdSftpConnections = SftpConnection::factory()
            ->forEachSequence(
                ['username' => 'test_username_1', 'import_filepath' => null],
                ['username' => 'test_username_2', 'export_filepath' => null]
            )
            ->for($this->company)
            ->create();

        Livewire::withQueryParams(['search' => 'test_username_1'])
            ->test(ListPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.list-page')
            ->assertViewHas(
                'sftpConnections',
                fn (LengthAwarePaginator $sftpConnections): bool => $sftpConnections->total() === 1 && $sftpConnections->getCollection()->first()->is($createdSftpConnections->first())
            )
            ->assertDontSee(__('No result found'))
            ->assertSee($createdSftpConnections->first()->port)
            ->assertSee($createdSftpConnections->first()->host)
            ->assertSee(__('Export only'));
    }

    #[Test]
    public function it_can_delete_the_sftp_connection(): void
    {
        $sftpConnection = SftpConnection::factory()
            ->for($this->company)
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->call('delete', $sftpConnection->id)
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('SFTP deleted.'));

        $this->assertModelMissing($sftpConnection);
    }

    #[Test]
    public function it_can_delete_the_sftp_connection_but_attached_with_headers(): void
    {
        $sftpConnection = SftpConnection::factory()
            ->for($this->company)
            ->has(CsvHeader::factory())
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->call('delete', $sftpConnection->id)
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Cannot deleted, this SFTP connection is linked to header files.'));

        $this->assertDatabaseHas(SftpConnection::class, ['id' => $sftpConnection->id]);
    }

    #[Test]
    public function it_can_enabled_and_disabled_sftp_connection(): void
    {
        $sftpConnection = SftpConnection::factory()
            ->for($this->company)
            ->create(['enabled' => false]);

        Livewire::test(ListPage::class)
            ->assertOk()
            ->call('toggleEnabled', $sftpConnection->id);

        Notification::assertNotified(__('SFTP Connection enabled successfully.'));

        $this->assertTrue($sftpConnection->refresh()->enabled);
    }
}
