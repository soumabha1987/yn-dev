<?php

declare(strict_types=1);

namespace Tests\Feature\ImportConsumers;

use App\Enums\CompanyStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ImportConsumers\UploadFilePage;
use App\Models\CsvHeader;
use App\Models\Merchant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class UploadFilePageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_page(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        Merchant::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        $this->get(route('creditor.import-consumers.upload-file'))
            ->assertSeeLivewire(UploadFilePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_the_livewire_page(): void
    {
        $createdCsvHeaders = CsvHeader::factory()
            ->forEachSequence(
                ['is_mapped' => true],
                ['is_mapped' => false],
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $this->user->subclient_id,
                'headers' => [],
            ]);

        Livewire::test(UploadFilePage::class)
            ->assertViewIs('livewire.creditor.import-consumers.upload-file-page')
            ->assertViewHas(
                'csvHeaders',
                fn (Collection $csvHeaders): bool => $csvHeaders->first() === Str::title($createdCsvHeaders->first()->name) . ' ' . __('(Completed)')
                && $csvHeaders->last() === Str::title($createdCsvHeaders->last()->name) . ' ' . __('(Incomplete)')
            )
            ->assertSee(__('Proceed to Mapping'))
            ->assertSee(__('SFTP Import'))
            ->assertOk();
    }

    #[Test]
    public function it_can_set_the_selected_header_when_select_from_dropdown_the_header(): void
    {
        $csvHeader = CsvHeader::factory()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'headers' => [],
        ]);

        Livewire::test(UploadFilePage::class)
            ->set('selectedHeaderId', $csvHeader->id)
            ->assertSet('selectedHeader.id', $csvHeader->id)
            ->assertSet('selectedHeader.headers', $csvHeader->headers)
            ->assertSet('selectedHeader.is_mapped', $csvHeader->is_mapped)
            ->assertOk();
    }

    #[Test]
    public function it_can_delete_csv_header(): void
    {
        $csvHeader = CsvHeader::factory()->create([
            'company_id' => $this->user->company_id,
            'headers' => [],
        ]);

        Livewire::test(UploadFilePage::class)
            ->call('deleteSelectedHeader', $csvHeader)
            ->assertDispatched('close-confirmation-box')
            ->assertOk();

        $this->assertModelMissing($csvHeader);
    }

    #[Test]
    public function it_can_create_the_csv_header_but_throw_validation_errors(): void
    {
        Livewire::test(UploadFilePage::class)
            ->set('form.header_name', fake()->word())
            ->set('form.header_file', UploadedFile::fake()->create('avatar.jpg'))
            ->call('createHeader')
            ->assertHasErrors('form.header_file')
            ->assertSee(__('Invalid file format. Please upload a valid CSV file.'));
    }

    #[Test]
    public function it_can_create_the_csv_header(): void
    {
        $data = ['Header1', 'Header2', 'Header3'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        Livewire::test(UploadFilePage::class)
            ->set('form.header_name', $name = fake()->word())
            ->set('form.header_file', $file)
            ->call('createHeader')
            ->assertHasNoErrors()
            ->assertOk();

        $csvHeader = CsvHeader::first();

        $this->assertEquals($csvHeader->import_headers, $data);
        $this->assertEquals($csvHeader->name, $name);
        $this->assertEquals($csvHeader->is_mapped, false);
    }

    #[Test]
    public function it_can_download_csv_header_file(): void
    {
        $csvHeader = CsvHeader::factory()->create([
            'name' => fake()->word(),
            'company_id' => $this->user->company_id,
            'import_headers' => ['test'],
        ]);

        Livewire::test(UploadFilePage::class)
            ->call('downloadUploadedFile', $csvHeader)
            ->assertFileDownloaded('header.csv')
            ->assertOk();
    }

    #[Test]
    public function it_can_import_same_header_file_throw_validation_error(): void
    {
        $data = ['Header1', 'Header2', 'Header3'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        $csvHeader = CsvHeader::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $this->user->subclient_id,
                'import_Headers' => $data,
            ]);

        Livewire::test(UploadFilePage::class)
            ->set('form.header_name', $name = fake()->word())
            ->set('form.header_file', $file)
            ->call('createHeader')
            ->assertHasErrors(['form.header_file' => __('Header matches existing profile, [<b>:name</b>]', ['name' => $csvHeader->name])])
            ->assertOk();

        $this->assertDatabaseMissing(CsvHeader::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'name' => $name,
            'headers' => $file,
        ]);
    }

    #[Test]
    public function it_can_import_different_header_file_passed(): void
    {
        $data = ['Header1', 'Header2', 'Header3'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        CsvHeader::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $this->user->subclient_id,
                'import_headers' => ['Header1', 'Header2', 'Header4'],
            ]);

        $this->assertDatabaseCount(CsvHeader::class, 1);

        Livewire::test(UploadFilePage::class)
            ->set('form.header_name', fake()->word())
            ->set('form.header_file', $file)
            ->call('createHeader')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseCount(CsvHeader::class, 2);
    }

    #[Test]
    public function it_can_render_with_blank_space_import_header_file_passed(): void
    {
        $data = ['Header1', '', 'Header2', 'Header3'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        Livewire::test(UploadFilePage::class)
            ->set('form.header_name', $name = fake()->word())
            ->set('form.header_file', $file)
            ->call('createHeader')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertDatabaseMissing(CsvHeader::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'name' => $name,
            'headers' => $file,
        ]);
    }
}
