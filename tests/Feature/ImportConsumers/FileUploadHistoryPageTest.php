<?php

declare(strict_types=1);

namespace Tests\Feature\ImportConsumers;

use App\Enums\CreditorCurrentStep;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ImportConsumers\FileUploadHistoryPage;
use App\Models\FileUploadHistory;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class FileUploadHistoryPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.import-consumers.file-upload-history'))
            ->assertSeeLivewire(FileUploadHistoryPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file(): void
    {
        Livewire::test(FileUploadHistoryPage::class)
            ->assertViewIs('livewire.creditor.import-consumers.file-upload-history-page')
            ->assertSee(__('No result found'))
            ->assertViewHas('fileUploadHistories')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_file_upload_histories(): void
    {
        $fileUploadHistory = FileUploadHistory::create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => FileUploadHistoryType::ADD,
            'total_records' => 5000,
        ]);

        Livewire::test(FileUploadHistoryPage::class)
            ->assertViewIs('livewire.creditor.import-consumers.file-upload-history-page')
            ->assertViewHas('fileUploadHistories', fn (LengthAwarePaginator $fileUploadHistories): bool => $fileUploadHistory->is($fileUploadHistories->getCollection()->first()))
            ->assertSee($fileUploadHistory->filename)
            ->assertSee($fileUploadHistory->total_records)
            ->assertSee($fileUploadHistory->processed_count)
            ->assertSee($fileUploadHistory->failed_count)
            ->assertSee($fileUploadHistory->type->fileHistoriesDisplayMessage())
            ->assertSee($fileUploadHistory->created_at->formatWithTimezone())
            ->assertSee(FileUploadHistoryStatus::VALIDATING->displayStatus())
            ->assertSet('sortCol', 'upload-date')
            ->assertSet('sortAsc', false)
            ->assertDontSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_can_display_only_applied_type_of_file_histories(): void
    {
        collect(range(1, 2))->each(function ($index) {
            FileUploadHistory::create([
                'company_id' => $this->user->company_id,
                'subclient_id' => $this->user->subclient_id,
                'uploaded_by' => $this->user->id,
                'filename' => 'headers.csv',
                'status' => FileUploadHistoryStatus::VALIDATING,
                'type' => $index === 1 ? FileUploadHistoryType::ADD : FileUploadHistoryType::DELETE,
                'total_records' => 5000,
            ]);
        });

        Livewire::test(FileUploadHistoryPage::class)
            ->set('typeFilter', FileUploadHistoryType::ADD)
            ->assertViewHas('fileUploadHistories', fn (LengthAwarePaginator $fileUploadHistories) => $fileUploadHistories->total() === 1 && $fileUploadHistories->getCollection()->first()->type === FileUploadHistoryType::ADD)
            ->assertOk();
    }

    #[Test]
    public function it_can_download_uploaded_file(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->create($fileName = 'headers.csv');

        $file->storeAs('import_consumers/', $fileName);

        $fileUploadHistory = FileUploadHistory::create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => $fileName,
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => FileUploadHistoryType::ADD,
            'total_records' => 5000,
        ]);

        Livewire::test(FileUploadHistoryPage::class)
            ->call('downloadUploadedFile', $fileUploadHistory)
            ->assertFileDownloaded($fileName)
            ->assertOk();
    }

    #[Test]
    public function it_can_download_failed_record_file(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->create($fileName = 'headers-failed.csv');

        $file->storeAs('import_consumers/', $fileName);

        $fileUploadHistory = FileUploadHistory::create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'failed_filename' => $fileName,
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => FileUploadHistoryType::ADD,
            'total_records' => 5000,
        ]);

        Livewire::test(FileUploadHistoryPage::class)
            ->call('downloadFailedFile', $fileUploadHistory)
            ->assertFileDownloaded($fileName)
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_created_at(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'created_at' => now()->addDays($sequence->index + 1),
            ])
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'upload-date')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_filename(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'filename' => range('A', 'Z')[$sequence->index],
            ])
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'name',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_type(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory()
            ->forEachSequence(
                ['type' => FileUploadHistoryType::ADD],
                ['type' => FileUploadHistoryType::DELETE],
                ['type' => FileUploadHistoryType::UPDATE],
            )
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'type',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_total_records(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory(10)
            ->sequence(fn (Sequence $sequence): array => ['total_records' => ($sequence->index + 10)])
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'records',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'records')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_processed_count(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'processed_count' => ($sequence->index + 10),
            ])
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'successful-records',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'successful-records')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_failed_count(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory(10)
            ->sequence(fn (Sequence $sequence) => [
                'failed_count' => ($sequence->index + 10),
            ])
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'failed-records',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'failed-records')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory()
            ->forEachSequence(
                ['status' => FileUploadHistoryStatus::FAILED],
                ['status' => FileUploadHistoryStatus::VALIDATING]
            )
            ->create(['company_id' => $this->user->company_id, 'is_hidden' => false]);

        Livewire::withQueryParams([
            'sort' => 'status',
            'direction' => $direction === 'ASC',
        ])
            ->test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'status')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'fileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $direction === 'ASC'
                    ? $createdFileUploadHistories->first()->is($fileUploadHistories->getCollection()->first())
                    : $createdFileUploadHistories->last()->is($fileUploadHistories->getCollection()->first())
            );
    }

    #[Test]
    public function it_can_delete_history_from_list(): void
    {
        $fileUploadHistory = FileUploadHistory::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'is_hidden' => false,
            ]);

        Livewire::test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.import-consumers.file-upload-history-page')
            ->call('delete', $fileUploadHistory->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Deleted successfully.'));

        $this->assertEquals($fileUploadHistory->refresh()->is_hidden, true);
    }

    #[Test]
    public function it_can_render_file_uploaded_history_list(): void
    {
        FileUploadHistory::factory(4)
            ->create([
                'company_id' => $this->user->company_id,
                'is_hidden' => true,
            ]);

        Livewire::test(FileUploadHistoryPage::class)
            ->assertOk()
            ->assertViewIs('livewire.creditor.import-consumers.file-upload-history-page')
            ->assertSee('No result found');
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}
