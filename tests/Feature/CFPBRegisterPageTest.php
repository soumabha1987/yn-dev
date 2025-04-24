<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\FileUploadHistoryStatus;
use App\Enums\FileUploadHistoryType;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Jobs\ConsumersDownloadCFPBRegisterLetter;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\CFPBRegisterPage;
use App\Models\AutomatedCommunicationHistory;
use App\Models\CommunicationStatus;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerELetter;
use App\Models\ConsumerProfile;
use App\Models\ELetter;
use App\Models\FileUploadHistory;
use App\Models\MembershipPaymentProfile;
use App\Models\YnTransaction;
use App\Services\TilledPaymentService;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class CFPBRegisterPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.cfpb-communication'))
            ->assertSeeLivewire(CFPBRegisterPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file(): void
    {
        Livewire::test(CFPBRegisterPage::class)
            ->assertViewIs('livewire.creditor.cfpb-register-page')
            ->assertSee(__('No result found'))
            ->assertViewHas('cfpbFileUploadHistories')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_file_with_data(): void
    {
        $createdFileUploadHistories = FileUploadHistory::factory()
            ->has(
                Consumer::factory(20)->state([
                    'status' => ConsumerStatus::JOINED,
                    'company_id' => $this->company->id,
                ])
            )
            ->forEachSequence(
                ['type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB],
                ['type' => FileUploadHistoryType::ADD]
            )
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
            ]);

        $cfpbFileUploadHistory = $createdFileUploadHistories->first();

        Livewire::test(CFPBRegisterPage::class)
            ->assertViewHas(
                'cfpbFileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $fileUploadHistories->getCollection()->contains($cfpbFileUploadHistory)
                && $fileUploadHistories->getCollection()->doesntContain($createdFileUploadHistories->last())
            )
            ->assertSee($cfpbFileUploadHistory->created_at->formatWithTimezone())
            ->assertSee(str($cfpbFileUploadHistory->filename)->limit(35)->toString())
            ->assertSee((string) $cfpbFileUploadHistory->activeConsumers->count())
            ->assertSee(__('Send Secure EcoLetters'))
            ->assertSee(__('Download & Print Letters'))
            ->assertSee(__('Delete'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_livewire_component_has_view_active_consumer(): void
    {
        $cfpbFileUploadHistory = FileUploadHistory::factory()
            ->has(
                Consumer::factory($activeConsumersCount = 10)->state([
                    'status' => ConsumerStatus::JOINED,
                    'company_id' => $this->company->id,
                ])
            )
            ->has(
                Consumer::factory($inActiveConsumersCount = 8)->state([
                    'status' => fake()->randomElement([ConsumerStatus::DISPUTE, ConsumerStatus::DEACTIVATED, ConsumerStatus::NOT_PAYING]),
                    'company_id' => $this->company->id,
                ])
            )
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
                'cfpb_hidden' => false,
            ]);

        Livewire::test(CFPBRegisterPage::class)
            ->assertViewHas(
                'cfpbFileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $fileUploadHistories->getCollection()->contains($cfpbFileUploadHistory)
            )
            ->assertSee($cfpbFileUploadHistory->created_at->formatWithTimezone())
            ->assertSee(str($cfpbFileUploadHistory->filename)->limit(35)->toString())
            ->assertSee((string) $activeConsumersCount)
            ->assertSee(__('Send Secure EcoLetters'))
            ->assertSee(__('Download & Print Letters'))
            ->assertSee(__('Delete'))
            ->assertOk();

        $this->assertEquals($activeConsumersCount, $cfpbFileUploadHistory->activeConsumers->count());
        $this->assertNotEquals($inActiveConsumersCount, $cfpbFileUploadHistory->activeConsumers->count());
        $this->assertEquals(($activeConsumersCount + $inActiveConsumersCount), $cfpbFileUploadHistory->consumers->count());
    }

    #[Test]
    public function it_can_call_download_consumers_file(): void
    {
        Storage::fake();

        $fileUploadHistory = FileUploadHistory::factory()
            ->has(Consumer::factory()->state([
                'status' => ConsumerStatus::JOINED,
                'company_id' => $this->company->id,
            ]))
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
            ]);

        Livewire::test(CFPBRegisterPage::class)
            ->call('downloadUploadedFile', $fileUploadHistory->id)
            ->assertFileDownloaded()
            ->assertOk();
    }

    #[Test]
    public function it_can_call_cfpb_disable_file_upload_history(): void
    {
        $fileUploadHistory = FileUploadHistory::factory()
            ->has(Consumer::factory()->state([
                'status' => ConsumerStatus::JOINED,
                'company_id' => $this->company->id,
            ]))
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
            ]);

        Livewire::test(CFPBRegisterPage::class)
            ->assertViewHas(
                'cfpbFileUploadHistories',
                fn (LengthAwarePaginator $fileUploadHistories) => $fileUploadHistories->getCollection()->contains($fileUploadHistory)
            )
            ->call('cfpbDisable', $fileUploadHistory->id)
            ->assertOk()
            ->assertDispatched('close-confirmation-box')
            ->assertSee(__('No result found'));

        Notification::assertNotified(__('List deleted.'));

        $this->assertEquals(1, $fileUploadHistory->refresh()->cfpb_hidden);
    }

    #[Test]
    public function it_can_call_download_letter_for_file_upload_history_below_100_consumer(): void
    {
        Storage::fake();

        Queue::fake();

        $fileUploadHistory = FileUploadHistory::factory()
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
                'processed_count' => $processedCount = 10,
            ]);

        Consumer::factory($processedCount)
            ->create([
                'company_id' => $this->company->id,
                'file_upload_history_id' => $fileUploadHistory->id,
            ]);

        Livewire::test(CFPBRegisterPage::class)
            ->call('downloadLetters', $fileUploadHistory->id)
            ->assertOk()
            ->assertFileDownloaded()
            ->assertDispatched('close-confirmation-box');

        Queue::assertNotPushed(ConsumersDownloadCFPBRegisterLetter::class);

        Notification::assertNotified(__('Letters ready for download.'));
    }

    #[Test]
    public function it_can_call_download_letter_for_file_upload_history_more_then_100_consumer(): void
    {
        Storage::fake();

        Queue::fake();

        $fileUploadHistory = FileUploadHistory::factory()
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
                'processed_count' => $processedCount = 101,
            ]);

        Consumer::factory($processedCount)
            ->create([
                'company_id' => $this->company->id,
                'file_upload_history_id' => $fileUploadHistory->id,
                'status' => ConsumerStatus::JOINED,
            ]);

        Livewire::test(CFPBRegisterPage::class)
            ->call('downloadLetters', $fileUploadHistory->id)
            ->assertOk()
            ->assertNoFileDownloaded()
            ->assertDispatched('close-confirmation-box');

        Queue::assertPushed(ConsumersDownloadCFPBRegisterLetter::class);

        Notification::assertNotified(__("It takes a bit of time when  downloading 100 or more letters. We will email you the link as soon as it's ready!"));
    }

    #[Test]
    public function it_can_call_secure_eco_letter_for_file_upload_history(): void
    {
        Storage::fake();

        Queue::fake();

        $companyMembership = CompanyMembership::factory()->create(['company_id' => $this->company->id]);

        MembershipPaymentProfile::factory()->create(['company_id' => $this->company->id]);

        $ecoMailAmount = $companyMembership->membership->e_letter_fee;

        $communicationStatus = CommunicationStatus::factory()
            ->create([
                'code' => CommunicationCode::CFPB_ECO_MAIL,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        $fileUploadHistory = FileUploadHistory::factory()
            ->create([
                'company_id' => $this->company->id,
                'status' => FileUploadHistoryStatus::COMPLETE,
                'cfpb_hidden' => false,
                'type' => FileUploadHistoryType::ADD_ACCOUNT_WITH_CREATE_CFPB,
                'processed_count' => $processedCount = 10,
            ]);

        Consumer::factory($processedCount)
            ->sequence([
                'consumer_profile_id' => ConsumerProfile::factory()->create([
                    'text_permission' => true,
                    'email_permission' => true,
                    'email' => 'test@test.com',
                    'mobile' => '9006790067',
                ]),
            ])
            ->create([
                'company_id' => $this->company->id,
                'file_upload_history_id' => $fileUploadHistory->id,
                'subclient_id' => null,
                'status' => ConsumerStatus::JOINED,
            ]);

        $this->assertDatabaseCount(AutomatedCommunicationHistory::class, 0);

        $this->assertDatabaseCount(ELetter::class, 0);

        $this->assertDatabaseCount(ConsumerELetter::class, 0);

        $this->partialMock(TilledPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentIntents')
                ->once()
                ->withAnyArgs()
                ->andReturn(['status' => 'succeeded']);
        });

        Livewire::test(CFPBRegisterPage::class)
            ->set('withQrCode', true)
            ->call('secureEcoLetters', $fileUploadHistory->id)
            ->assertOk()
            ->assertSet('withQrCode', true)
            ->assertNoFileDownloaded();

        Notification::assertNotified(__('All secure EcoLetters have been successfully sent.'));

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $this->company->id,
            'amount' => number_format($processedCount * $ecoMailAmount, 2, thousands_separator: ''),
            'email_count' => 0,
            'sms_count' => 0,
            'eletter_count' => $processedCount,
            'phone_no_count' => 0,
            'email_cost' => 0,
            'sms_cost' => 0,
            'eletter_cost' => number_format($processedCount * $ecoMailAmount, 2, thousands_separator: ''),
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, $processedCount);

        $this->assertDatabaseCount(ELetter::class, 1);

        $this->assertDatabaseCount(ConsumerELetter::class, $processedCount);
    }
}
