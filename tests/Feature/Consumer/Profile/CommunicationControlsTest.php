<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Profile;

use AllowDynamicProperties;
use App\Livewire\Consumer\Profile\CommunicationControls;
use App\Models\Consumer;
use App\Models\ConsumerProfile;
use App\Models\ConsumerUnsubscribe;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class CommunicationControlsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()
            ->for(ConsumerProfile::factory()->create(['is_communication_updated' => false]))
            ->create();

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_the_route(): void
    {
        $this->get(route('consumer.communication_controls'))
            ->assertOk()
            ->assertSeeLivewire(CommunicationControls::class);
    }

    #[Test]
    public function it_can_render_livewire_component_correct_view(): void
    {
        Livewire::test(CommunicationControls::class)
            ->assertViewIs('livewire.consumer.profile.communication-controls')
            ->assertOk();
    }

    #[Test]
    public function it_can_update_text_permission(): void
    {
        $this->consumer->consumerProfile()->update(['email_permission' => true]);

        $textPermission = $this->consumer->consumerProfile->text_permission;

        Livewire::test(CommunicationControls::class)
            ->call('updateTextPermission')
            ->assertDispatched('update-text-permission')
            ->assertOk();

        $this->assertNotEquals($textPermission, $this->consumer->consumerProfile->refresh()->text_permission);
        $this->assertTrue($this->consumer->consumerProfile->refresh()->is_communication_updated);
    }

    #[Test]
    public function it_can_not_allow_blank_mobile_number(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.mobile', '')
            ->call('updateMobile')
            ->assertHasErrors(['form.mobile' => ['required']])
            ->assertOk();
    }

    #[Test]
    public function can_only_allow_US_phone_number(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.mobile', '1234567')
            ->call('updateMobile')
            ->assertHasErrors(['form.mobile' => ['phone']])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_phone_number(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.mobile', '(248) 348-4577')
            ->call('updateMobile')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotified(__('Mobile number updated successfully.'));
        Notification::assertNotNotified(__('Mobile number already updated'));

        $this->assertEquals('2483484577', $this->consumer->consumerProfile->refresh()->mobile);
        $this->assertTrue($this->consumer->consumerProfile->refresh()->is_communication_updated);
    }

    #[Test]
    public function it_can_update_same_phone_number(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.mobile', $mobile = $this->consumer->consumerProfile->mobile)
            ->call('updateMobile')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotNotified(__('Mobile number updated successfully.'));

        $this->assertEquals($mobile, $this->consumer->consumerProfile->refresh()->mobile);
    }

    #[Test]
    public function it_can_update_email_permission(): void
    {
        $this->consumer->consumerProfile()->update(['email_permission' => true]);

        $emailPermission = $this->consumer->consumerProfile->email_permission;

        Livewire::test(CommunicationControls::class)
            ->call('updateEmailPermission')
            ->assertDispatched('update-email-permission')
            ->assertOk();

        $this->assertNotEquals($emailPermission, $this->consumer->consumerProfile->refresh()->email_permission);
    }

    #[Test]
    public function it_can_update_email_permission_to_true_and_remove_consumer_unsubscribe(): void
    {
        $this->consumer->consumerProfile()
            ->update([
                'email_permission' => false,
                'text_permission' => true,
            ]);

        $unsubscribeConsumer = ConsumerUnsubscribe::factory()
            ->for($this->consumer, 'consumer')
            ->create([
                'company_id' => $this->consumer->company_id,
                'email' => $this->consumer->consumerProfile->email,
            ]);

        $this->assertNotNull($unsubscribeConsumer->email);

        $emailPermission = $this->consumer->consumerProfile->email_permission;

        Livewire::test(CommunicationControls::class)
            ->call('updateEmailPermission')
            ->assertDispatched('update-email-permission')
            ->assertOk();

        $this->assertNull($unsubscribeConsumer->refresh()->email);
        $this->assertNotEquals($this->consumer->consumerProfile->refresh()->email_permission, $emailPermission);
    }

    #[Test]
    public function it_can_not_allow_nullable_email(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.email', '')
            ->call('updateEmail')
            ->assertHasErrors(['form.email' => ['required']])
            ->assertOk();

        $this->assertNotNull($this->consumer->consumerProfile->refresh()->email);
    }

    #[Test]
    public function it_can_update_new_email_address(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.email', 'joe@laravel.com')
            ->call('updateEmail')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotified(__('Email updated successfully.'));
        Notification::assertNotNotified(__('Email field already updated'));

        $this->assertEquals('joe@laravel.com', $this->consumer->consumerProfile->refresh()->email);
        $this->assertTrue($this->consumer->consumerProfile->refresh()->is_communication_updated);
    }

    #[Test]
    public function it_can_update_same_email_address(): void
    {
        Livewire::test(CommunicationControls::class)
            ->set('form.email', $email = $this->consumer->consumerProfile->email)
            ->call('updateEmail')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotNotified(__('Email updated successfully.'));

        $this->assertEquals($email, $this->consumer->consumerProfile->refresh()->email);
    }

    #[Test]
    public function it_can_update_communication_flag_when_update_communication_settings(): void
    {
        Livewire::test(CommunicationControls::class)
            ->call('confirmCommunicationSettings')
            ->assertHasNoErrors()
            ->assertOk()
            ->assertRedirectToRoute('consumer.account');

        Notification::assertNotified(__('You have successfully updated communications.'));

        $this->assertTrue($this->consumer->consumerProfile->refresh()->is_communication_updated);
    }
}
