<?php

declare(strict_types=1);

namespace Tests\Feature\SftpConnection;

use App\Livewire\Creditor\SftpConnection\AttachHeaderProfile;
use App\Models\CsvHeader;
use App\Models\SftpConnection;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachHeaderProfileTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component_with_data(): void
    {
        $sftpConnections = SftpConnection::factory()
            ->for($this->user->company)
            ->forEachSequence(
                ['import_filepath' => 'test/import', 'export_filepath' => null],
                ['import_filepath' => null, 'export_filepath' => 'test/export'],
                ['import_filepath' => 'test/import', 'export_filepath' => 'test/export'],
            )
            ->create(['enabled' => true]);

        Livewire::actingAs($this->user)
            ->test(AttachHeaderProfile::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.sftp-connection.attach-header-profile')
            ->assertViewHas('sftpConnections', fn (array $sftpConnections) => count($sftpConnections) === 2)
            ->assertViewHas('headers', fn (Collection $headers) => $headers->isEmpty());
    }

    #[Test]
    public function it_can_attach_header_profile_with_sftp_connection(): void
    {
        $csvHeader = CsvHeader::factory()->create();

        $sftpConnection = SftpConnection::factory()
            ->create([
                'export_filepath' => null,
                'import_filepath' => 'test/import',
                'enabled' => true,
            ]);

        $this->assertNull($csvHeader->sftp_connection_id);

        Livewire::actingAs($this->user)
            ->test(AttachHeaderProfile::class)
            ->assertOk()
            ->call('attach', $sftpConnection->id, $csvHeader->id)
            ->assertOk();

        Notification::assertNotified(__('SFTP added to header profile.'));

        $this->assertNotNull($csvHeader->refresh()->sftp_connection_id);
    }

    #[Test]
    public function it_can_detach_header_profile_with_sftp_connection(): void
    {
        $csvHeader = CsvHeader::factory()
            ->for(SftpConnection::factory())
            ->create();

        $this->assertNotNull($csvHeader->sftp_connection_id);

        Livewire::actingAs($this->user)
            ->test(AttachHeaderProfile::class)
            ->assertOk()
            ->call('attach', '', $csvHeader->id)
            ->assertOk();

        Notification::assertNotified(__('SFTP removed from header profile.'));

        $this->assertNull($csvHeader->refresh()->sftp_connection_id);
    }
}
