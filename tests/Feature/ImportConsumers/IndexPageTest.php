<?php

declare(strict_types=1);

namespace Tests\Feature\ImportConsumers;

use App\Enums\CompanyStatus;
use App\Enums\ConsumerFields;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Enums\MerchantName;
use App\Enums\Role as EnumRole;
use App\Jobs\CalculateTotalRecordsJob;
use App\Jobs\ImportConsumersJob;
use App\Livewire\Creditor\ImportConsumers\IndexPage;
use App\Models\Consumer;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\FileUploadHistory;
use App\Models\Merchant;
use App\Models\PersonalizedLogo;
use Filament\Notifications\Notification;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user->update(['subclient_id' => null]);

        // TODO: we need to test that merchant is different for display modal!
        Merchant::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'verified_at' => null,
                'merchant_name' => MerchantName::STRIPE,
            ]);

        $this->company->update(['status' => CompanyStatus::ACTIVE]);
    }

    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);
        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
        ]);

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        $file = UploadedFile::fake()->create('test.csv');

        $data = [['EMAIL_ID'], ['test@test.com']];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Merchant::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        $this->get(route('creditor.import-consumers.index'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_page_with_defined_view(): void
    {
        $this->user->company()->update([
            'pif_balance_discount_percent' => 2.42,
            'ppa_balance_discount_percent' => 201.33,
            'min_monthly_pay_percent' => 9.23,
            'max_days_first_pay' => 10,
        ]);

        Storage::fake();

        $this->makeCsvHeader();

        UploadedFile::fake()->create('test.csv');

        $data = [['EMAIL_ID'], ['test@test.com']];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->assertViewIs('livewire.creditor.import-consumers.index-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_called_hook_and_set_csv_header(): void
    {
        $csvHeader = $this->makeCsvHeader();

        $this->user->update(['subclient_id' => null]);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->assertSet('selectedHeader.id', $csvHeader->id)
            ->assertSet('selectedHeader.headers', [
                ConsumerFields::CONSUMER_EMAIL->displayName() => 'EMAIL_ID',
                ConsumerFields::ACCOUNT_NUMBER->displayName() => 'ACCOUNT_NUMBER',
            ])
            ->assertViewHas('csvHeaders', fn (Collection $csvHeaders): bool => $csvHeader->id === $csvHeaders->first()['id'])
            ->assertOk();
    }

    #[Test]
    public function it_can_upload_import_consumer_file(): void
    {
        $csvHeader = $this->makeCsvHeader();

        $file = UploadedFile::fake()->create('test.csv');

        $data = ['Header1', 'Header2', 'Header3'];

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->assertOk()
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();
    }

    #[Test]
    public function it_can_throw_validation_error_of_mime_types(): void
    {
        $this->user->update(['subclient_id' => null]);

        $csvHeader = $this->makeCsvHeader();

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', FileUploadHistoryType::ADD)
            ->set('form.import_file', UploadedFile::fake()->create('test.png'))
            ->call('importConsumers')
            ->assertHasErrors('form.import_file', ['mime_types'])
            ->assertOk();
    }

    #[Test]
    public function it_can_return_when_selected_headers_and_files_are_not_the_same_header(): void
    {
        Queue::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        $file = UploadedFile::fake()->create('test.csv');

        $data = ['Header1', 'Header2', 'Header3'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertMissing('import_consumers/headers.csv');
        $this->assertDatabaseCount(FileUploadHistory::class, 0);
        Queue::assertNotPushed(ImportConsumersJob::class);
    }

    #[Test]
    public function it_can_check_please_add_data_with_headers(): void
    {
        Queue::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        $file = UploadedFile::fake()->create('test.csv');

        $data = ['EMAIL_ID'];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertMissing('import_consumers/headers.csv');
        $this->assertDatabaseCount(FileUploadHistory::class, 0);
        Queue::assertNotPushed(ImportConsumersJob::class);
    }

    #[Test]
    public function it_can_check_company_membership_consumer_zero_consumer_upload_account_limit(): void
    {
        Queue::fake();

        Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'company_id' => $this->user->company_id,
        ]);

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        $data = [['EMAIL_ID', 'ACCOUNT_NUMBER'], ['email_id', 'test@test.com']];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        $this->companyMembership->membership()->update(['upload_accounts_limit' => 1]);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertMissing('import_consumers/headers.csv');
        $this->assertDatabaseCount(FileUploadHistory::class, 0);
        Queue::assertNotPushed(ImportConsumersJob::class);
    }

    #[Test]
    public function it_can_check_company_membership_consumer_upload_account_limit(): void
    {
        Bus::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        $data = [['EMAIL_ID', 'ACCOUNT_NUMBER'], ['email_id', 'test@test.com'], ['email_id_2', 'test_2@test.com'], ['email_id_3', 'test_3@test.com']];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fputcsv($stream, $data[2]);

        @fputcsv($stream, $data[3]);

        @fclose($stream);

        $this->companyMembership->membership()->update(['upload_accounts_limit' => 1]);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertExists('import_consumers/headers.csv');

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_create_file_upload_history_and_store_the_file(): void
    {
        $this->user->update(['subclient_id' => null]);

        Bus::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        UploadedFile::fake()->create('test.csv');

        $data = [['EMAIL_ID', 'ACCOUNT_NUMBER'], ['test@test.com', fake()->uuid()]];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertExists('import_consumers/headers.csv');

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_create_file_upload_history_and_store_the_file_with_new_line_added(): void
    {
        $this->user->update(['subclient_id' => null]);

        Bus::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        UploadedFile::fake()->create('test.csv');

        $data = [['EMAIL_ID', 'ACCOUNT_NUMBER'], ['test@test.com' . PHP_EOL, fake()->uuid()]];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::ADD)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk();

        Storage::assertExists('import_consumers/headers.csv');

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_redirect_wizard_setup_page_when_remain_required_steps(): void
    {
        $this->user->update(['subclient_id' => null]);

        Livewire::test(IndexPage::class)
            ->assertRedirectToRoute('creditor.setup-wizard')
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function it_can_check_mark_for_delete_for_consumers(): void
    {
        $this->user->update(['subclient_id' => null]);

        Bus::fake();

        Storage::fake();

        $csvHeader = $this->makeCsvHeader();

        UploadedFile::fake()->create('test.csv');

        $data = [['EMAIL_ID', 'ACCOUNT_NUMBER'], ['test@test.com', fake()->uuid()]];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::DELETE)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertSet('selectedHeader.headers', [ConsumerFields::ACCOUNT_NUMBER->displayName() => 'ACCOUNT_NUMBER']);

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        Storage::assertExists('import_consumers/headers.csv');

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_update_consumer_file(): void
    {
        $this->user->update(['subclient_id' => null]);

        Bus::fake();

        Storage::fake();

        $this->makeCsvHeader();

        $csvHeader = CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'mapped_headers' => [
                ConsumerFields::CONSUMER_EMAIL->value => 'Email id',
                ConsumerFields::PHONE->value => 'Consumer Mobile Number',
                ConsumerFields::ACCOUNT_NUMBER->value => 'Account number',
                ConsumerFields::PAY_IN_FULL_DISCOUNT_PERCENTAGE->value => 'Pif percentage',
                ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->value => 'Ppa percentage',
                ConsumerFields::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->value => 'Min monthly percentage',
                ConsumerFields::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->value => 'Max days first pay',
            ],
        ]);

        $data = [
            ['Email id', 'Consumer Mobile Number', 'Account number', 'Pif percentage', 'Ppa percentage', 'Min monthly percentage', 'Max days first pay'],
            ['test@test.com', '9009090045', fake()->uuid(), fake()->numberBetween(0, 100), fake()->numberBetween(0, 100), fake()->numberBetween(0, 100), fake()->numberBetween(0, 1000)],
        ];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::UPDATE)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertSet('selectedHeader.headers', [
                ConsumerFields::ACCOUNT_NUMBER->displayName() => 'Account number',
                ConsumerFields::CONSUMER_EMAIL->displayName() => 'Email id',
                ConsumerFields::PHONE->displayName() => 'Consumer Mobile Number',
                ConsumerFields::PAY_IN_FULL_DISCOUNT_PERCENTAGE->displayName() => 'Pif percentage',
                ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName() => 'Ppa percentage',
                ConsumerFields::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->displayName() => 'Min monthly percentage',
                ConsumerFields::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->displayName() => 'Max days first pay',
            ]);

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        Storage::assertExists('import_consumers/headers.csv');

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_update_only_email_and_phone_number_of_consumer_file(): void
    {
        $this->user->update(['subclient_id' => null]);

        Bus::fake();

        Storage::fake();

        $this->makeCsvHeader();

        $csvHeader = CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'mapped_headers' => [
                ConsumerFields::CONSUMER_EMAIL->value => 'Email id',
                ConsumerFields::PHONE->value => 'Consumer Mobile Number',
                ConsumerFields::ACCOUNT_NUMBER->value => 'Account number',
            ],
        ]);

        $data = [
            ['Email id', 'Consumer Mobile Number', 'Account number'],
            ['test@test.com', '9009090045', fake()->uuid()],
        ];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::UPDATE)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertSet('selectedHeader.headers', [
                ConsumerFields::ACCOUNT_NUMBER->displayName() => 'Account number',
                ConsumerFields::CONSUMER_EMAIL->displayName() => 'Email id',
                ConsumerFields::PHONE->displayName() => 'Consumer Mobile Number',
            ]);

        Bus::assertChained([
            CalculateTotalRecordsJob::class,
            ImportConsumersJob::class,
        ]);

        Storage::assertExists('import_consumers/headers.csv');

        $this->assertDatabaseHas(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 0,
        ]);
    }

    #[Test]
    public function it_can_required_minimum_fields_mapped_on_update_consumer(): void
    {
        $this->user->update(['subclient_id' => null]);

        Queue::fake();

        $this->makeCsvHeader();

        $csvHeader = CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'mapped_headers' => [
                ConsumerFields::CONSUMER_EMAIL->value => 'Email id',
                ConsumerFields::PAY_IN_FULL_DISCOUNT_PERCENTAGE->value => 'Pif percentage',
            ],
        ]);

        $data = [
            ['Email id', 'Pif percentage'],
            ['test@test.com', fake()->numberBetween(0, 100)],
        ];

        $file = UploadedFile::fake()->create('headers.csv');

        $stream = @fopen($file->path(), 'w');

        @fputcsv($stream, $data[0]);

        @fputcsv($stream, $data[1]);

        @fclose($stream);

        Livewire::test(IndexPage::class)
            ->set('form.header', $csvHeader->id)
            ->set('form.import_type', $importType = FileUploadHistoryType::UPDATE)
            ->set('form.import_file', $file)
            ->call('importConsumers')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertNotSet('selectedHeader.headers', [
                ConsumerFields::ACCOUNT_NUMBER->displayName() => 'Account number',
                ConsumerFields::PAY_IN_FULL_DISCOUNT_PERCENTAGE->displayName() => 'Pif percentage',
                ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName() => 'Ppa percentage',
            ]);

        Notification::assertNotified(__('The selected header file does not contain the updated field mappings.'));

        Queue::assertNotPushed(ImportConsumersJob::class);

        $this->assertDatabaseMissing(FileUploadHistory::class, [
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'headers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING->value,
            'type' => $importType,
            'total_records' => 1,
        ]);
    }

    private function makeCsvHeader(): CsvHeader
    {
        PersonalizedLogo::query()->create([
            'company_id' => $this->user->company_id,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'customer_communication_link' => fake()->word(),
        ]);

        CustomContent::factory()
            ->forEachSequence(
                ['type' => CustomContentType::TERMS_AND_CONDITIONS],
                ['type' => CustomContentType::ABOUT_US]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
            ]);

        Merchant::factory()
            ->for($this->company)
            ->create([
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        return CsvHeader::query()->create([
            'name' => fake()->word(),
            'subclient_id' => null,
            'company_id' => $this->user->company_id,
            'is_mapped' => true,
            'mapped_headers' => [
                ConsumerFields::CONSUMER_EMAIL->value => 'EMAIL_ID',
                ConsumerFields::ACCOUNT_NUMBER->value => 'ACCOUNT_NUMBER',
            ],
        ]);
    }
}
