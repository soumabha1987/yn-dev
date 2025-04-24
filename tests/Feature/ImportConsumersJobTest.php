<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsumerFields;
use App\Enums\ConsumerStatus;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Jobs\ImportConsumersJob;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\CsvHeader;
use App\Models\FileUploadHistory;
use App\Models\Reason;
use App\Models\Subclient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportConsumersJobTest extends TestCase
{
    public User $user;

    public FileUploadHistory $fileUploadHistory;

    public int $accountNumber = 12345;

    public array $csvHeaders;

    #[Test]
    public function it_can_create_consumers(): void
    {
        Storage::fake();

        $this->makeRecords(FileUploadHistoryType::ADD);

        ImportConsumersJob::dispatchSync($this->fileUploadHistory, $this->csvHeaders, 5);

        $this->assertDatabaseCount(Consumer::class, 3);
        $this->assertDatabaseCount(ConsumerProfile::class, 3);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_update_consumers_for_same_account_number_deleted_consumers_with_same_company(): void
    {
        Storage::fake();

        $this->makeRecords(FileUploadHistoryType::ADD);

        $consumers = Consumer::factory()
            ->forEachSequence(
                [
                    'status' => ConsumerStatus::DEACTIVATED,
                    'account_number' => $this->accountNumber,
                ],
                [
                    'status' => ConsumerStatus::DISPUTE,
                    'account_number' => $this->accountNumber + 1,
                ],
                [
                    'status' => ConsumerStatus::NOT_PAYING,
                    'reason_id' => Reason::factory()->state([]),
                    'account_number' => $this->accountNumber + 2,
                ],
            )
            ->create([
                'company_id' => $this->fileUploadHistory->company_id,
            ]);

        ImportConsumersJob::dispatchSync($this->fileUploadHistory, $this->csvHeaders, 5);

        $this->assertDatabaseCount(Consumer::class, 3);

        $this->assertEquals(ConsumerStatus::UPLOADED, $consumers->first()->refresh()->status);
        $this->assertEquals(ConsumerStatus::UPLOADED, $consumers->last()->refresh()->status);
        $this->assertNull($consumers->last()->reason_id);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_create_consumers_for_same_account_number_deleted_consumers_with_different_company(): void
    {
        Storage::fake();

        $this->makeRecords(FileUploadHistoryType::ADD);

        Consumer::factory(3)
            ->sequence(fn (Sequence $sequence) => ['account_number' => $this->accountNumber + $sequence->index])
            ->create([
                'status' => ConsumerStatus::DEACTIVATED,
            ]);

        ImportConsumersJob::dispatchSync($this->fileUploadHistory, $this->csvHeaders, 5);

        $this->assertDatabaseCount(Consumer::class, 6);

        $this->assertEquals(2, Consumer::query()->where('account_number', $this->accountNumber)->count());

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_create_same_consumers(): void
    {
        Storage::fake();

        $this->makeRecords(FileUploadHistoryType::ADD, consumerHaveSameLastNameSsnAndDob: true);

        ImportConsumersJob::dispatch($this->fileUploadHistory, $this->csvHeaders, 5);

        $this->assertDatabaseCount(Consumer::class, 3);
        $this->assertDatabaseCount(ConsumerProfile::class, 1);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_create_consumers_with_only_remain_consumer_limit_only_one(): void
    {
        Storage::fake();

        $this->makeRecords(FileUploadHistoryType::ADD, consumerHaveSameLastNameSsnAndDob: true);

        ImportConsumersJob::dispatch($this->fileUploadHistory, $this->csvHeaders, 1);

        $this->assertDatabaseCount(Consumer::class, 1);
        $this->assertDatabaseCount(ConsumerProfile::class, 1);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 1);
        $this->assertEquals($this->fileUploadHistory->failed_count, 4);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_delete_without_active_plan_consumers(): void
    {
        Storage::fake();

        $passedConsumers = $this->makeRecords(FileUploadHistoryType::DELETE, updateOrDelete: true);

        $passedConsumers = collect($passedConsumers)->map(function ($passedConsumer) {
            return [
                ...$passedConsumer,
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::UPLOADED->value,
            ];
        })->toArray();

        $passedConsumers[0]['status'] = ConsumerStatus::PAYMENT_ACCEPTED->value;
        $passedConsumers[1]['status'] = ConsumerStatus::DEACTIVATED->value;

        Consumer::query()->insert($passedConsumers);

        ImportConsumersJob::dispatch($this->fileUploadHistory, $this->csvHeaders, 1);

        $this->assertDatabaseHas(Consumer::class, [
            'account_number' => $passedConsumers[0]['account_number'],
            'email1' => $passedConsumers[0]['email1'],
            'status' => ConsumerStatus::DEACTIVATED,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'account_number' => $passedConsumers[1]['account_number'],
            'email1' => $passedConsumers[1]['email1'],
            'status' => ConsumerStatus::DEACTIVATED,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'account_number' => $passedConsumers[2]['account_number'],
            'email1' => $passedConsumers[2]['email1'],
            'status' => ConsumerStatus::DEACTIVATED,
            'deleted_at' => null,
        ]);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($this->fileUploadHistory->processed_count, 2);
        $this->assertEquals($this->fileUploadHistory->failed_count, 3);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_update_consumers_with_update_consumer_profile(): void
    {
        Storage::fake();

        $uploadedFileData = $this->makeRecords(FileUploadHistoryType::UPDATE, updateOrDelete: true);

        $consumerProfile = ConsumerProfile::factory()->create([
            'email' => 'test_update@test.com',
            'mobile' => '9009090090',
            'email_permission' => false,
            'text_permission' => false,
        ]);

        $passedConsumers = collect($uploadedFileData)->map(function ($passedConsumer) use ($consumerProfile) {
            return [
                ...$passedConsumer,
                'email1' => $consumerProfile->email,
                'mobile1' => $consumerProfile->mobile,
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::UPLOADED->value,
                'consumer_profile_id' => $consumerProfile->id,
            ];
        })->toArray();

        Consumer::query()->insert($passedConsumers);

        $this->csvHeaders['headers'] = [
            ConsumerFields::CONSUMER_EMAIL->displayName() => 'consumer email',
            ConsumerFields::PHONE->displayName() => 'consumer mobile number',
            ConsumerFields::ACCOUNT_NUMBER->displayName() => 'original account number',
            ConsumerFields::PAY_IN_FULL_DISCOUNT_PERCENTAGE->displayName() => 'pif discount percentage',
            ConsumerFields::PAYMENT_PLAN_BALANCE_DISCOUNT_PERCENTAGE->displayName() => 'pif setup discount percentage',
            ConsumerFields::PAYMENT_PLAN_MIN_MONTHLY_PAYMENT_PERCENTAGE_OF_BALANCE->displayName() => 'min monthly pay percentage',
            ConsumerFields::PAYMENT_PLAN_MAX_DAYS_FIRST_PAY->displayName() => 'max days first pay',
        ];

        ImportConsumersJob::dispatch($this->fileUploadHistory, $this->csvHeaders, 1);

        $this->assertDatabaseHas(Consumer::class, [
            'email1' => $uploadedFileData[0]['email1'],
            'mobile1' => $uploadedFileData[0]['mobile1'],
            'account_number' => $uploadedFileData[0]['account_number'],
            'pif_discount_percent' => $uploadedFileData[0]['pif_discount_percent'],
            'pay_setup_discount_percent' => $uploadedFileData[0]['pay_setup_discount_percent'],
            'min_monthly_pay_percent' => $uploadedFileData[0]['min_monthly_pay_percent'],
            'max_days_first_pay' => $uploadedFileData[0]['max_days_first_pay'],
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'email1' => $uploadedFileData[1]['email1'],
            'mobile1' => $uploadedFileData[1]['mobile1'],
            'account_number' => $uploadedFileData[1]['account_number'],
            'pif_discount_percent' => $uploadedFileData[1]['pif_discount_percent'],
            'pay_setup_discount_percent' => $uploadedFileData[1]['pay_setup_discount_percent'],
            'min_monthly_pay_percent' => $uploadedFileData[1]['min_monthly_pay_percent'],
            'max_days_first_pay' => $uploadedFileData[1]['max_days_first_pay'],
        ]);

        $this->assertDatabaseHas(Consumer::class, [
            'email1' => $uploadedFileData[2]['email1'],
            'mobile1' => $uploadedFileData[2]['mobile1'],
            'account_number' => $uploadedFileData[2]['account_number'],
            'pif_discount_percent' => $uploadedFileData[2]['pif_discount_percent'],
            'pay_setup_discount_percent' => $uploadedFileData[2]['pay_setup_discount_percent'],
            'min_monthly_pay_percent' => $uploadedFileData[2]['min_monthly_pay_percent'],
            'max_days_first_pay' => $uploadedFileData[2]['max_days_first_pay'],
        ]);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($uploadedFileData[0]['email1'], $consumerProfile->refresh()->email);
        $this->assertEquals($uploadedFileData[0]['mobile1'], $consumerProfile->mobile);
        $this->assertTrue($consumerProfile->text_permission);
        $this->assertTrue($consumerProfile->email_permission);

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_update_consumers_with_update_only_mobile_and_email_consumer_profile(): void
    {
        Storage::fake();

        $uploadedFileData = $this->makeRecords(FileUploadHistoryType::UPDATE, updateOrDelete: true);

        $consumerProfile = ConsumerProfile::factory()->create([
            'email' => 'gogo_update@test.com',
            'mobile' => '9009090090',
        ]);

        $passedConsumers = collect($uploadedFileData)->map(function ($passedConsumer) use ($consumerProfile) {
            return [
                ...$passedConsumer,
                'email1' => $consumerProfile->email,
                'mobile1' => $consumerProfile->mobile,
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::UPLOADED->value,
                'consumer_profile_id' => $consumerProfile->id,
            ];
        })->toArray();

        Consumer::query()->insert($passedConsumers);

        $this->csvHeaders['headers'] = [
            ConsumerFields::CONSUMER_EMAIL->displayName() => 'consumer email',
            ConsumerFields::PHONE->displayName() => 'consumer mobile number',
            ConsumerFields::ACCOUNT_NUMBER->displayName() => 'original account number',
        ];

        ImportConsumersJob::dispatch($this->fileUploadHistory, $this->csvHeaders, 1);

        $this->assertDatabaseHas(Consumer::class, [
            'mobile1' => $uploadedFileData[0]['mobile1'],
            'email1' => $uploadedFileData[0]['email1'],
            'account_number' => $uploadedFileData[0]['account_number'],
            'pif_discount_percent' => $uploadedFileData[0]['pif_discount_percent'],
            'pay_setup_discount_percent' => $uploadedFileData[0]['pay_setup_discount_percent'],
            'min_monthly_pay_percent' => $uploadedFileData[0]['min_monthly_pay_percent'],
            'max_days_first_pay' => $uploadedFileData[0]['max_days_first_pay'],
        ]);

        $this->assertTrue(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $this->fileUploadHistory->refresh();

        $this->assertEquals($uploadedFileData[0]['email1'], $consumerProfile->refresh()->email);
        $this->assertEquals($uploadedFileData[0]['mobile1'], $consumerProfile->refresh()->mobile);

        $this->assertEquals($this->fileUploadHistory->processed_count, 3);
        $this->assertEquals($this->fileUploadHistory->failed_count, 2);
        $this->assertEquals($this->fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);

        $file = @fopen(Storage::path('import_consumers/' . $this->fileUploadHistory->failed_filename), 'r');

        $this->assertTrue(collect(@fgetcsv($file))->contains('Errors'));

        @fclose($file);
    }

    #[Test]
    public function it_can_skip_blank_line_and_trimmed_whole_row_of_uploaded_file(): void
    {
        Storage::fake();

        $user = User::factory()->create(['subclient_id' => null]);

        $file = UploadedFile::fake()->create('my-consumers.csv');

        $csvHeader = CsvHeader::factory()
            ->for($user->company)
            ->create([
                'subclient_id' => null,
                'is_mapped' => true,
                'mapped_headers' => [
                    'original_account_name' => 'consumer original name',
                    'email1' => 'consumer email',
                    'last_name' => 'consumer last name',
                    'first_name' => 'consumer first name',
                    'dob' => 'date of birth',
                    'last4ssn' => 'last four ssn',
                    'mobile1' => 'consumer mobile number',
                    'account_number' => 'original account number',
                    'current_balance' => 'beginning account balance',
                    'pif_discount_percent' => 'pif discount percentage',
                    'pay_setup_discount_percent' => 'pif setup discount percentage',
                    'min_monthly_pay_percent' => 'min monthly pay percentage',
                    'max_days_first_pay' => 'max days first pay',
                ],
            ]);

        $stream = @fopen($file->path(), 'a+');

        @fputcsv($stream, array_values($csvHeader->mapped_headers));
        @fputcsv($stream, [
            'original_account_name' => 'consumer original name',
            'email1' => 'test@gmail.com     ',
            'last_name' => 'LastName',
            'first_name' => 'FirstName',
            'dob' => fake()->date(max: now()->subDays(10)),
            'last4ssn' => fake()->numberBetween(1000, 9999),
            'mobile1' => '+1 (900) 509-0060',
            'account_number' => '11111',
            'current_balance' => '9,999',
            'pif_discount_percent' => fake()->boolean() ? fake()->numberBetween(0, 99) : null,
            'pay_setup_discount_percent' => $isInstallmentOffer = fake()->boolean ? fake()->numberBetween(0, 99) : null,
            'min_monthly_pay_percent' => $isInstallmentOffer !== null ? fake()->numberBetween(1, 99) : null,
            'max_days_first_pay' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ]);
        @fputcsv($stream, [
            'original_account_name' => '    ',
            'email1' => '    ',
            'last_name' => '    ',
            'first_name' => '    ',
            'dob' => '      ',
            'last4ssn' => '      ',
            'mobile1' => '      ',
            'account_number' => '      ',
            'current_balance' => '      ',
            'pif_discount_percent' => '      ',
            'pay_setup_discount_percent' => '      ',
            'min_monthly_pay_percent' => '      ',
            'max_days_first_pay' => '      ',
        ]);
        @fputcsv($stream, [
            'original_account_name' => 'consumer original name   ',
            'email1' => 'test1@gmail.com',
            'last_name' => 'LastName',
            'first_name' => 'FirstName',
            'dob' => fake()->date(max: now()->subDays(10)),
            'last4ssn' => fake()->numberBetween(1000, 9999),
            'mobile1' => fake()->randomElement(['900-50-90-060', '(900)-509-0060', '900@50 90060', '900 50 900 60', '9005090060', '+1 (900) 509-0060']),
            'account_number' => '22222',
            'current_balance' => '999',
            'pif_discount_percent' => fake()->boolean() ? fake()->numberBetween(0, 99) : null,
            'pay_setup_discount_percent' => $isInstallmentOffer = fake()->boolean ? fake()->numberBetween(0, 99) : null,
            'min_monthly_pay_percent' => $isInstallmentOffer !== null ? fake()->numberBetween(1, 99) : null,
            'max_days_first_pay' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ]);
        @fclose($stream);

        $file->storeAs('import_consumers/', 'my-consumers.csv');

        $fileUploadHistory = FileUploadHistory::query()->create([
            'company_id' => $user->company_id,
            'subclient_id' => $user->subclient_id,
            'uploaded_by' => $user->id,
            'filename' => 'my-consumers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => FileUploadHistoryType::ADD,
            'total_records' => 3,
        ]);

        ImportConsumersJob::dispatchSync($fileUploadHistory, [
            'id' => $csvHeader->id,
            'name' => $csvHeader->name,
            'date_format' => $csvHeader->date_format,
            'headers' => collect($csvHeader->mapped_headers)
                ->mapWithKeys(fn ($key, $header) => [ConsumerFields::tryFromValue($header)->displayName() => $key])
                ->toArray(),
        ], 13);

        $this->assertDatabaseCount(Consumer::class, 2)
            ->assertDatabaseHas(Consumer::class, [
                'email1' => 'test1@gmail.com',
                'original_account_name' => 'consumer original name',
            ])
            ->assertDatabaseHas(Consumer::class, [
                'email1' => 'test@gmail.com',
                'original_account_name' => 'consumer original name',
            ]);
        $this->assertDatabaseCount(ConsumerProfile::class, 2);

        $this->assertFalse(Storage::exists('import_consumers/my-consumers-failed.csv'));

        $fileUploadHistory->refresh();

        $this->assertEquals($fileUploadHistory->processed_count, 2);
        $this->assertEquals($fileUploadHistory->failed_count, 0);
        $this->assertEquals($fileUploadHistory->status, FileUploadHistoryStatus::COMPLETE);
    }

    private function makeRecords(FileUploadHistoryType $type, bool $consumerHaveSameLastNameSsnAndDob = false, bool $updateOrDelete = false): array
    {
        $this->user = User::factory()->create(['subclient_id' => null]);

        $file = UploadedFile::fake()->create('my-consumers.csv');

        $subclient = Subclient::factory()
            ->for($this->user->company)
            ->create([
                'unique_identification_number' => 'MH-123',
            ]);

        $csvHeader = CsvHeader::factory()
            ->for($this->user->company)
            ->create([
                'subclient_id' => null,
                'is_mapped' => true,
                'mapped_headers' => [
                    'original_account_name' => 'consumer original name',
                    'email1' => 'consumer email',
                    'last_name' => 'consumer last name',
                    'first_name' => 'consumer first name',
                    'dob' => 'date of birth',
                    'last4ssn' => 'last four ssn',
                    'mobile1' => 'consumer mobile number',
                    'account_number' => 'original account number',
                    'current_balance' => 'beginning account balance',
                    'subclient_id' => 'subclient identification number',
                    'pif_discount_percent' => 'pif discount percentage',
                    'pay_setup_discount_percent' => 'pif setup discount percentage',
                    'min_monthly_pay_percent' => 'min monthly pay percentage',
                    'max_days_first_pay' => 'max days first pay',
                ],
            ]);

        $stream = @fopen($file->path(), 'a+');

        @fputcsv($stream, array_values($csvHeader->mapped_headers));

        collect(range(1, 3))->each(function (int $value, int $index) use ($stream, &$passedConsumers, $consumerHaveSameLastNameSsnAndDob, $subclient, $updateOrDelete): void {
            @fputcsv($stream, array_values($passedConsumers[] = [
                'original_account_name' => 'consumer original name',
                'email1' => $consumerHaveSameLastNameSsnAndDob ? 'test_' . $index . '@gmail.com' : 'test@gmail.com',
                'last_name' => $consumerHaveSameLastNameSsnAndDob ? 'Consumer' : 'LastName',
                'first_name' => 'FirstName',
                'dob' => $consumerHaveSameLastNameSsnAndDob ? '2020-12-12' : fake()->date(max: now()->subDays(10)),
                'last4ssn' => $consumerHaveSameLastNameSsnAndDob ? '1223' : fake()->numberBetween(1000, 9999),
                'mobile1' => $consumerHaveSameLastNameSsnAndDob ? 9005090050 + $index : 9005090060,
                'account_number' => $this->accountNumber + $index,
                'current_balance' => '999',
                'subclient_id' => 'MH-123',
                'pif_discount_percent' => fake()->boolean() ? fake()->numberBetween(0, 99) : null,
                'pay_setup_discount_percent' => $isInstallmentOffer = fake()->boolean ? fake()->numberBetween(0, 99) : null,
                'min_monthly_pay_percent' => $isInstallmentOffer !== null ? fake()->numberBetween(1, 99) : null,
                'max_days_first_pay' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
            ]));

            if ($updateOrDelete) {
                $passedConsumers[$index]['subclient_id'] = $subclient->id;
            }
        });

        collect(range(1, 2))->each(function () use ($stream): void {
            @fputcsv($stream, [
                'consumer original name',
                'test@gmail.com',
                Str::limit(fake()->lastName(), 20),
                Str::limit(fake()->firstName(), 20),
                'test date',
                fake()->numberBetween(1000, 9999),
                '9005090050',
                fake()->randomNumber(4, true),
                '999',
                'MH-123',
                fake()->boolean ? fake()->numberBetween(0, 99) : null,
                fake()->boolean ? fake()->numberBetween(0, 99) : null,
                fake()->boolean ? fake()->numberBetween(1, 99) : null,
                fake()->boolean ? fake()->numberBetween(1, 1000) : null,
            ]);
        });

        $file->storeAs('import_consumers/', 'my-consumers.csv');

        $this->csvHeaders = [
            'id' => $csvHeader->id,
            'name' => $csvHeader->name,
            'date_format' => $csvHeader->date_format,
            'headers' => collect($csvHeader->mapped_headers)
                ->mapWithKeys(fn ($key, $header) => [ConsumerFields::tryFromValue($header)->displayName() => $key])
                ->toArray(),
        ];

        $this->fileUploadHistory = FileUploadHistory::query()->create([
            'company_id' => $this->user->company_id,
            'subclient_id' => $this->user->subclient_id,
            'uploaded_by' => $this->user->id,
            'filename' => 'my-consumers.csv',
            'status' => FileUploadHistoryStatus::VALIDATING,
            'type' => $type,
            'total_records' => 5,
        ]);

        return $passedConsumers;
    }
}
