<?php

declare(strict_types=1);

namespace Tests\Feature\ImportConsumers;

use App\Enums\CreditorCurrentStep;
use App\Enums\FileUploadHistoryDateFormat;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ImportConsumers\MapUploadedFilePage;
use App\Models\CsvHeader;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class MapUploadedFilePageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()->for($this->company)->create();

        $this->get(route('creditor.import-consumers.upload-file.map', $csvHeader->id))
            ->assertSeeLivewire(MapUploadedFilePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_view_page_with_data(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()->for($this->company)->create();

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->assertViewIs('livewire.creditor.import-consumers.map-uploaded-file-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_call_exit_and_do_not_save(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()->for($this->company)->create();

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->call('deleteHeader')
            ->assertOk()
            ->assertRedirectToRoute('creditor.import-consumers.upload-file');

        Notification::assertNotified(__('The header file was not saved and was successfully deleted.'));

        $this->assertModelMissing($csvHeader);
    }

    #[Test]
    public function it_can_render_call_exit_and_do_not_save_when_exists_mapped_headers(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->user->update(['subclient_id' => null]);

        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()
            ->for($this->company)
            ->create([
                'mapped_headers' => [
                    'email' => 'Email',
                    'account_number' => 'Account Number',
                ],
            ]);

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->call('deleteHeader')
            ->assertOk()
            ->assertRedirectToRoute('creditor.import-consumers.upload-file');

        Notification::assertNotNotified(__('The header file was not saved and was successfully deleted.'));

        $this->assertModelExists($csvHeader);
    }

    #[Test]
    public function it_can_render_finish_later_mapped_least_one_header(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()->for($this->company)->create();

        $this->assertFalse(data_get($csvHeader->headers, 'mapped_headers', false));

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->set('mappedHeaders', [])
            ->call('finishLater')
            ->assertOk()
            ->assertHasNoErrors(['mappedHeaders' => __('Map a minimum of one mapped data field to finish later')]);
    }

    #[Test]
    public function it_can_render_finish_later_of_import_headers_file(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()->for($this->company)->create();

        $this->assertFalse(data_get($csvHeader->headers, 'mapped_headers', false));

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->set('date_format', $dateFormat = fake()->randomElement(FileUploadHistoryDateFormat::values()))
            ->set(
                'mappedHeaders',
                $mappedHeaders = [
                    'account_number' => 'account number',
                    'last_name' => 'last name',
                    'dob' => 'date of birth',
                ]
            )
            ->call('finishLater')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($mappedHeaders, data_get($csvHeader->refresh()->headers, 'mapped_headers'));
        $this->assertEquals($dateFormat, $csvHeader->date_format);
        $this->assertFalse($csvHeader->is_mapped);
    }

    #[Test]
    public function it_can_render_edit_import_headers_file(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $csvHeader = CsvHeader::factory()
            ->for($this->company)
            ->create([
                'mapped_headers' => [
                    'account_number' => 'account number',
                    'last_name' => 'last name',
                    'dob' => 'date of birth',
                ],
            ]);

        $this->assertNotNull(data_get($csvHeader->headers, 'mapped_headers', false));

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->set('date_format', $dateFormat = fake()->randomElement(FileUploadHistoryDateFormat::values()))
            ->set(
                'mappedHeaders',
                $updatedMappedHeaders = [
                    'account_number' => 'account number',
                    'first_name' => 'first name',
                    'dob' => 'date of birth',
                    'mobile' => 'mobile',
                    'email' => 'email',
                ]
            )
            ->call('finishLater')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($updatedMappedHeaders, data_get($csvHeader->refresh()->headers, 'mapped_headers'));
        $this->assertEquals($dateFormat, $csvHeader->date_format);
        $this->assertFalse($csvHeader->is_mapped);
    }

    #[Test]
    public function it_can_render_store_header_file_when_setup_wizard_steps_completed(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->user->update(['subclient_id' => null]);

        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 0);

        $csvHeader = CsvHeader::factory()
            ->for($this->company)
            ->create();

        $this->assertNotNull(data_get($csvHeader->headers, 'mapped_headers', false));

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->set('date_format', $dateFormat = fake()->randomElement(FileUploadHistoryDateFormat::values()))
            ->set(
                'mappedHeaders',
                $updatedMappedHeaders = [
                    'account_number' => 'account number',
                    'original_account_name' => 'account number',
                    'first_name' => 'first name',
                    'dob' => 'date of birth',
                    'email1' => 'email',
                    'last_name' => 'last name',
                    'last4ssn' => 'last 4 ssn',
                    'current_balance' => 'current balance',
                    'mobile1' => 'mobile number',
                    'member_account_number' => 'member account number',
                    'address1' => 'address line 1',
                    'address2' => 'address line 2',
                    'city' => 'city',
                    'state' => 'state',
                    'zip' => 'zip code',
                ]
            )
            ->call('storeMappedHeaders')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('creditor.import-consumers.upload-file'));

        $this->assertEquals($updatedMappedHeaders, data_get($csvHeader->refresh()->headers, 'mapped_headers'));
        $this->assertEquals($dateFormat, $csvHeader->date_format);
        $this->assertTrue($csvHeader->is_mapped);
    }

    #[Test]
    public function it_can_render_store_header_file_when_setup_wizard_steps_in_completed(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        Cache::put('remaining-wizard-required-steps-' . $this->user->id, 1);

        $csvHeader = CsvHeader::factory()
            ->for($this->company)
            ->create();

        $this->assertNotNull(data_get($csvHeader->headers, 'mapped_headers', false));

        Livewire::test(MapUploadedFilePage::class, ['csvHeader' => $csvHeader])
            ->set('date_format', $dateFormat = fake()->randomElement(FileUploadHistoryDateFormat::values()))
            ->set(
                'mappedHeaders',
                $updatedMappedHeaders = [
                    'account_number' => 'account number',
                    'original_account_name' => 'account number',
                    'first_name' => 'first name',
                    'dob' => 'date of birth',
                    'email1' => 'email',
                    'last_name' => 'last name',
                    'last4ssn' => 'last 4 ssn',
                    'current_balance' => 'current balance',
                    'mobile1' => 'mobile number',
                    'member_account_number' => 'member account number',
                    'address1' => 'address line 1',
                    'address2' => 'address line 2',
                    'city' => 'city',
                    'state' => 'state',
                    'zip' => 'zip code',
                ]
            )
            ->call('storeMappedHeaders')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertEquals($updatedMappedHeaders, data_get($csvHeader->refresh()->headers, 'mapped_headers'));
        $this->assertEquals($dateFormat, $csvHeader->date_format);
        $this->assertTrue($csvHeader->is_mapped);
    }
}
