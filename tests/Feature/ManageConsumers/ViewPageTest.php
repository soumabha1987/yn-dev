<?php

declare(strict_types=1);

namespace Tests\Feature\ManageConsumers;

use App\Enums\AutomatedCommunicationHistoryStatus;
use App\Enums\AutomatedTemplateType;
use App\Enums\CommunicationCode;
use App\Enums\CommunicationStatusTriggerType;
use App\Enums\CompanyMembershipStatus;
use App\Enums\ConsumerStatus;
use App\Enums\NegotiationType;
use App\Enums\Role as EnumRole;
use App\Enums\TransactionStatus;
use App\Jobs\TriggerEmailAndSmsServiceJob;
use App\Livewire\Creditor\ManageConsumers\ViewPage;
use App\Mail\AutomatedTemplateMail;
use App\Models\AutomatedCommunicationHistory;
use App\Models\AutomatedTemplate;
use App\Models\CommunicationStatus;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\ConsumerNegotiation;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use App\Models\ScheduleTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ViewPageTest extends AuthTestCase
{
    public Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consumer = Consumer::factory()
            ->has(ConsumerNegotiation::factory()->state(['active_negotiation' => true]))
            ->for(ConsumerProfile::factory()->create([
                'email_permission' => true,
                'text_permission' => true,
            ]))
            ->create(['status' => ConsumerStatus::JOINED]);
    }

    #[Test]
    public function it_can_render_livewire_page(): void
    {
        $this->get(route('manage-consumers.view', $this->consumer->id))
            ->assertOk()
            ->assertSeeLivewire(ViewPage::class);
    }

    #[Test]
    public function it_can_render_deleted_company_consumer_display(): void
    {
        $consumer = Consumer::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create();

        ConsumerNegotiation::factory()
            ->create([
                'consumer_id' => $consumer->id,
                'negotiation_type' => fake()->randomElement([NegotiationType::PIF, NegotiationType::INSTALLMENT]),
            ]);

        $this->get(route('manage-consumers.view', $consumer->id))
            ->assertOk();
    }

    #[Test]
    public function it_can_not_display_negotiation_related_fields(): void
    {
        $this->get(route('manage-consumers.view', $this->consumer->id))
            ->assertOk()
            ->assertSeeLivewire(ViewPage::class)
            ->assertDontSee(__('Reset Negotiation'))
            ->assertDontSee(__('Send E-Letter'));
    }

    #[Test]
    public function it_can_render_also_other_livewire_components(): void
    {
        $consumerNegotiation = $this->consumer->consumerNegotiation;

        $statuses = $this->selectionBoxStatuses();

        $consumerStatus = match (true) {
            $this->consumer->status !== ConsumerStatus::PAYMENT_ACCEPTED => $statuses[$this->consumer->status->value] ?? 'N/A',
            $this->consumer->payment_setup => __('Active Payment Plan'),
            $consumerNegotiation->negotiation_type === NegotiationType::PIF => __('Agreed Settlement/Pending Payment'),
            $consumerNegotiation->negotiation_type === NegotiationType::INSTALLMENT => __('Agreed Payment Plan/Pending Payment'),
        };

        $this->get(route('manage-consumers.view', $this->consumer->id))
            ->assertOk()
            ->assertSeeLivewire(ViewPage::class)
            ->assertSee($consumerStatus)
            ->assertSee(__('Transaction History'))
            ->assertSee(__('Payment Plan'))
            ->assertSee(__('Schedule Cancelled Payment Details'))
            ->assertSee(__('E-Letters Histories'));
    }

    #[Test]
    public function it_can_display_consumer_negotiation_displays(): void
    {
        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertSeeLivewire(ViewPage::class)
            ->assertViewHas('consumerNegotiation', fn ($consumerNegotiation) => $consumerNegotiation !== null);
    }

    #[Test]
    public function it_can_display_other_company_consumer(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $consumer = Consumer::factory()->create();

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertStatus(403);
    }

    #[Test]
    public function it_can_display_only_to_superadmin(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->assertOk()
            ->assertSeeLivewire(ViewPage::class)
            ->assertSee(__('Send Email'))
            ->assertSee(__('Send SMS'));
    }

    #[Test]
    public function it_can_update_some_details_of_consumer(): void
    {
        $this->consumer->update([
            'status' => ConsumerStatus::UPLOADED,
            'email1' => 'test@test.com',
            'mobile1' => 9005090050,
        ]);

        $this->consumer->consumerProfile()->update([
            'email' => 'test@test.com',
            'mobile' => 9005090050,
        ]);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->set('form.email', 'test_12345@test.com')
            ->set('form.mobile', 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($this->consumer->refresh()->email1, 'test_12345@test.com');
        $this->assertEquals($this->consumer->mobile1, 9006090060);
    }

    #[Test]
    public function it_can_update_mobile_and_email_where_consumer_and_consumer_profile_matches_with_uploaded_status(): void
    {
        $consumer = Consumer::factory()
            ->for($consumerProfile = ConsumerProfile::factory()->create([
                'mobile' => $mobile = '9009090090',
                'email' => $email = 'test@test.com',
            ]))
            ->create([
                'status' => ConsumerStatus::UPLOADED,
                'mobile1' => $mobile,
                'email1' => $email,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->set('form.email', $updatedEmail = 'test_12345@test.com')
            ->set('form.mobile', $updatedMobile = 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($consumer->refresh()->email1, $updatedEmail);
        $this->assertEquals($consumer->mobile1, $updatedMobile);
        $this->assertEquals($consumerProfile->refresh()->email, $updatedEmail);
        $this->assertEquals($consumerProfile->mobile, $updatedMobile);
    }

    #[Test]
    public function it_can_update_mobile_and_email_where_consumer_and_consumer_profile_only_email_matches_with_uploaded_status(): void
    {
        $consumer = Consumer::factory()
            ->for($consumerProfile = ConsumerProfile::factory()->create([
                'mobile' => '9009090090',
                'email' => $email = 'test@test.com',
            ]))
            ->create([
                'status' => ConsumerStatus::UPLOADED,
                'mobile1' => '9006790067',
                'email1' => $email,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->set('form.email', $updatedEmail = 'test_12345@test.com')
            ->set('form.mobile', $updatedMobile = 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($consumer->refresh()->email1, $updatedEmail);
        $this->assertEquals($consumer->mobile1, $updatedMobile);
        $this->assertNotEquals($consumerProfile->refresh()->email, $updatedEmail);
        $this->assertNotEquals($consumerProfile->mobile, $updatedMobile);
    }

    #[Test]
    public function it_can_update_mobile_and_email_where_consumer_and_consumer_profile_not_matches_with_uploaded_status(): void
    {
        $consumer = Consumer::factory()
            ->for($consumerProfile = ConsumerProfile::factory()->create([
                'mobile' => '9009090090',
                'email' => 'test@test.com',
            ]))
            ->create([
                'status' => ConsumerStatus::UPLOADED,
                'mobile1' => '9006790067',
                'email1' => 'test_consumer@test.com',
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->set('form.email', $updatedEmail = 'test_12345@test.com')
            ->set('form.mobile', $updatedMobile = 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($consumer->refresh()->email1, $updatedEmail);
        $this->assertEquals($consumer->mobile1, $updatedMobile);
        $this->assertNotEquals($consumerProfile->refresh()->email, $updatedEmail);
        $this->assertNotEquals($consumerProfile->mobile, $updatedMobile);
    }

    #[Test]
    public function it_can_update_mobile_and_email_where_consumer_and_consumer_profile_matches_with_non_uploaded_status(): void
    {
        $consumer = Consumer::factory()
            ->for($consumerProfile = ConsumerProfile::factory()->create([
                'mobile' => $mobile = '9009090090',
                'email' => $email = 'test@test.com',
            ]))
            ->create([
                'status' => ConsumerStatus::JOINED,
                'mobile1' => $mobile,
                'email1' => $email,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->set('form.email', $updatedEmail = 'test_12345@test.com')
            ->set('form.mobile', $updatedMobile = 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertEquals($consumer->refresh()->email1, $updatedEmail);
        $this->assertEquals($consumer->mobile1, $updatedMobile);
        $this->assertNotEquals($consumerProfile->refresh()->email, $updatedEmail);
        $this->assertNotEquals($consumerProfile->mobile, $updatedMobile);
    }

    #[Test]
    public function it_can_update_settled_consumer(): void
    {
        $this->consumer->update(['status' => ConsumerStatus::SETTLED]);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->set('form.email', 'test_12345@test.com')
            ->set('form.mobile', 9006090060)
            ->call('updateConsumer')
            ->assertOk()
            ->assertHasNoErrors();

        $this->consumer->refresh();

        $this->assertNotEquals($this->consumer->email1, 'test_12345@test.com');
        $this->assertNotEquals($this->consumer->mobile1, 9006090060);
    }

    #[Test]
    public function it_can_give_validation_error_on_update_consumer(): void
    {
        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->set('form.mobile', '')
            ->set('form.email', '')
            ->call('updateConsumer')
            ->assertHasErrors('form.mobile', ['required'])
            ->assertHasErrors('form.email', ['required']);
    }

    #[Test]
    public function super_admin_can_send_an_email(): void
    {
        Mail::fake();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->set('emailSubject', fake()->sentence(3))
            ->set('emailContent', fake()->sentence())
            ->call('sendEmail')
            ->assertDispatched('close-dialog')
            ->assertOk();

        Mail::assertQueued(AutomatedTemplateMail::class);
    }

    #[Test]
    public function subject_and_content_is_required(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('sendEmail')
            ->assertSet('emailSubject', '')
            ->assertSet('emailContent', '')
            ->assertHasErrors('emailSubject', ['required'])
            ->assertHasErrors('emailContent', ['required']);
    }

    #[Test]
    public function super_admin_can_send_an_sms(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->set('smsContent', fake()->sentence())
            ->call('sendSms')
            ->assertSet('isSuperAdmin', true)
            ->assertOk()
            ->assertDispatched('close-dialog');
    }

    #[Test]
    public function creditor_can_not_send_an_email(): void
    {
        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('sendEmail')
            ->assertSet('isSuperAdmin', false)
            ->assertNotDispatched('close-dialog')
            ->assertOk();
    }

    #[Test]
    public function creditor_can_not_send_an_sms(): void
    {
        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('sendSms')
            ->assertSet('isSuperAdmin', false)
            ->assertNotDispatched('close-dialog')
            ->assertOk();
    }

    #[Test]
    public function it_can_download_an_agreement_of_the_consumer(): void
    {
        Pdf::shouldReceive('setOption')->once()->andReturnSelf();
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('output')->once()->andReturn('Hello');

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('downloadAgreement', $this->consumer->id)
            ->assertFileDownloaded($this->consumer->account_number . '_you_negotiate_agreement.pdf');
    }

    #[Test]
    public function it_can_subscribe_consumer(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('toggleSubscription', $this->consumer->id)
            ->assertOk();

        $this->assertDatabaseCount(ConsumerUnsubscribe::class, 1);
    }

    #[Test]
    public function it_can_unsubscribe_consumer(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);
        $this->user->assignRole($role);

        ConsumerUnsubscribe::factory()->create([
            'consumer_id' => $this->consumer->id,
            'company_id' => $this->consumer->company_id,
            'phone' => $this->consumer->mobile1,
            'email' => $this->consumer->email1,
        ]);

        Livewire::test(ViewPage::class, ['consumer' => $this->consumer])
            ->call('toggleSubscription', $this->consumer->id)
            ->assertOk();

        $this->assertDatabaseCount(ConsumerUnsubscribe::class, 0);
    }

    #[Test]
    public function it_can_deactivate_if_consumer_status_is_not_uploaded(): void
    {
        $consumer = Consumer::factory()->create([
            'status' => ConsumerStatus::JOINED->value,
            'company_id' => Company::factory()->create()->id,
        ]);
        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->call('delete');

        $this->assertEquals($consumer->refresh()->status, ConsumerStatus::DEACTIVATED);
    }

    #[Test]
    public function it_can_deactivate_with_consumer_have_active_plan(): void
    {
        Mail::fake();

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'type' => AutomatedTemplateType::EMAIL,
            'user_id' => $this->user->id,
        ]);

        CommunicationStatus::factory()
            ->for($automatedTemplate, 'emailTemplate')
            ->for($automatedTemplate, 'smsTemplate')
            ->create([
                'code' => CommunicationCode::CREDITOR_REMOVED_ACCOUNT,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        $companyMembership = CompanyMembership::factory()
            ->create([
                'status' => CompanyMembershipStatus::ACTIVE,
                'current_plan_end' => today()->addMonth(),
            ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['email' => 'test@gmail.com', 'email_permission' => 1]))
            ->create([
                'status' => ConsumerStatus::PAYMENT_ACCEPTED->value,
                'company_id' => $companyMembership->company_id,
            ]);

        ConsumerNegotiation::factory()->create([
            'consumer_id' => $consumer->id,
            'negotiation_type' => fake()->randomElement([NegotiationType::PIF, NegotiationType::INSTALLMENT]),
        ]);

        $scheduleTransaction = ScheduleTransaction::factory()->create([
            'consumer_id' => $consumer->id,
            'status' => TransactionStatus::SCHEDULED,
        ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->call('delete')
            ->assertDispatched('refresh-parent')
            ->assertOk();

        Mail::assertQueued(AutomatedTemplateMail::class);

        $this->assertEquals($consumer->refresh()->status, ConsumerStatus::DEACTIVATED);

        $this->assertEquals($scheduleTransaction->refresh()->status, TransactionStatus::CANCELLED);
    }

    #[Test]
    public function it_can_send_an_email_when_uploaded_consumer_is_deleted(): void
    {
        Mail::fake();

        $automatedTemplate = AutomatedTemplate::factory()->create([
            'type' => AutomatedTemplateType::EMAIL,
            'user_id' => $this->user->id,
        ]);

        CommunicationStatus::factory()
            ->for($automatedTemplate, 'emailTemplate')
            ->for($automatedTemplate, 'smsTemplate')
            ->create([
                'code' => CommunicationCode::CREDITOR_REMOVED_ACCOUNT,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->state(['email' => 'test@gmail.com', 'email_permission' => 1]))
            ->create(['status' => ConsumerStatus::UPLOADED->value]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->call('delete')
            ->assertDispatched('refresh-parent')
            ->assertOk();

        Mail::assertQueued(AutomatedTemplateMail::class);

        $this->assertDatabaseHas(AutomatedCommunicationHistory::class, [
            'status' => AutomatedCommunicationHistoryStatus::SUCCESS->value,
        ]);
    }

    #[Test]
    public function it_can_not_send_an_email_for_unsubscribe_consumer_when_it_will_deleted(): void
    {
        Queue::fake();

        CommunicationStatus::factory()
            ->create([
                'code' => CommunicationCode::CREDITOR_REMOVED_ACCOUNT,
                'trigger_type' => CommunicationStatusTriggerType::AUTOMATIC,
            ]);

        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email_permission' => true,
                'text_permission' => true,
            ]))
            ->has(ConsumerUnsubscribe::factory()->for($this->user->company), 'unsubscribe')
            ->for($this->user->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->call('delete')
            ->assertDispatched('refresh-parent')
            ->assertOk();

        Queue::assertPushed(TriggerEmailAndSmsServiceJob::class, 1);
    }

    #[Test]
    public function it_can_see_update_permission_button(): void
    {
        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email' => $email = fake()->safeEmail(),
                'mobile' => $mobile = fake()->phoneNumber(),
                'email_permission' => true,
                'text_permission' => true,
            ]))
            ->create([
                'email1' => $email,
                'mobile1' => $mobile,
                'status' => ConsumerStatus::UPLOADED,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertSet('email_permission', true)
            ->assertSet('text_permission', true)
            ->assertSee(__('Update Permission'))
            ->assertSee($email)
            ->assertSee(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $mobile))
            ->assertOk();
    }

    #[Test]
    public function it_can_do_not_see_update_permission_button(): void
    {
        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email' => $email = fake()->safeEmail(),
                'mobile' => $mobile = fake()->phoneNumber(),
                'email_permission' => true,
                'text_permission' => true,
            ]))
            ->create([
                'status' => ConsumerStatus::UPLOADED,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertSet('email_permission', true)
            ->assertSet('text_permission', true)
            ->assertDontSee(__('Update Permission'))
            ->assertDontSee($email)
            ->assertDontSee(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $mobile))
            ->assertOk();
    }

    #[Test]
    public function it_can_see_update_permission_button_when_only_email_same_profile(): void
    {
        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email' => $email = fake()->safeEmail(),
                'mobile' => $mobile = fake()->phoneNumber(),
                'email_permission' => false,
                'text_permission' => false,
            ]))
            ->create([
                'email1' => $email,
                'status' => ConsumerStatus::UPLOADED,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertSet('email_permission', false)
            ->assertSet('text_permission', false)
            ->assertSee(__('Update Permission'))
            ->assertSee($email)
            ->assertDontSee(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $mobile))
            ->assertOk();
    }

    #[Test]
    public function it_can_call_update_email_permission(): void
    {
        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email' => $email = fake()->safeEmail(),
                'mobile' => $mobile = fake()->phoneNumber(),
                'email_permission' => false,
                'text_permission' => false,
            ]))
            ->create([
                'email1' => $email,
                'status' => ConsumerStatus::UPLOADED,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertSet('email_permission', false)
            ->assertSet('text_permission', false)
            ->assertSee(__('Update Permission'))
            ->assertSee($email)
            ->assertDontSee(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $mobile))
            ->call('updateEmailPermission')
            ->assertOk();

        $this->assertTrue($consumer->consumerProfile->refresh()->email_permission);
    }

    #[Test]
    public function it_can_call_update_sms_permission(): void
    {
        $consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create([
                'email' => $email = fake()->safeEmail(),
                'mobile' => $mobile = fake()->phoneNumber(),
                'email_permission' => false,
                'text_permission' => true,
            ]))
            ->create([
                'mobile1' => $mobile,
                'status' => ConsumerStatus::UPLOADED,
            ]);

        Livewire::test(ViewPage::class, ['consumer' => $consumer])
            ->assertSet('email_permission', false)
            ->assertSet('text_permission', true)
            ->assertSee(__('Update Permission'))
            ->assertDontSee($email)
            ->assertSee(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3', $mobile))
            ->call('updateTextPermission')
            ->assertOk();

        $this->assertFalse($consumer->consumerProfile->refresh()->text_permission);
    }

    private function selectionBoxStatuses(): array
    {
        return [
            ConsumerStatus::UPLOADED->value => __('Offer Delivered'),
            ConsumerStatus::JOINED->value => __('Offer Viewed'),
            ConsumerStatus::PAYMENT_SETUP->value => __('In Negotiations'),
            ConsumerStatus::SETTLED->value => __('Settled/Paid'),
            ConsumerStatus::DISPUTE->value => __('Disputed'),
            ConsumerStatus::NOT_PAYING->value => __('Reported Not Paying'),
            ConsumerStatus::PAYMENT_DECLINED->value => __('Negotiations Closed'),
            ConsumerStatus::DEACTIVATED->value => __('Deactivated'),
            ConsumerStatus::HOLD->value => __('Account in Hold'),
        ];
    }
}
