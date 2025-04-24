<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipInquiries;

use App\Enums\MembershipFeatures;
use App\Enums\MembershipFrequency;
use App\Enums\MembershipInquiryStatus;
use App\Livewire\Creditor\MembershipInquiries\ViewPage;
use App\Mail\ResolveSpecialMembershipInquiryRequestMail;
use App\Models\Membership;
use App\Models\MembershipInquiry;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewPageTest extends TestCase
{
    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $membershipInquiry = MembershipInquiry::factory()->create();

        Livewire::test(ViewPage::class, ['membershipInquiry' => $membershipInquiry])
            ->assertViewIs('livewire.creditor.membership-inquiries.view-page')
            ->assertSee(str($membershipInquiry->company->company_name)->title())
            ->assertSee($membershipInquiry->company->owner_email)
            ->assertSee($membershipInquiry->company->owner_phone)
            ->assertSee($membershipInquiry->description)
            ->assertSee(__('Company Details'))
            ->assertOk();
    }

    #[Test]
    public function it_can_render_create_special_membership(): void
    {
        Mail::fake();

        $membershipInquiry = MembershipInquiry::factory()->create(['status' => MembershipInquiryStatus::NEW_INQUIRY]);

        Livewire::test(ViewPage::class, ['membershipInquiry' => $membershipInquiry])
            ->set('form.name', $name = fake()->word())
            ->set('form.price', $price = fake()->randomNumber(3))
            ->set('form.fee', $fee = fake()->numberBetween(1, 100))
            ->set('form.e_letter_fee', $e_letter_fee = fake()->randomFloat(2, 0.1, 25))
            ->set('form.upload_accounts_limit', $uploadAccountsLimit = fake()->numberBetween(1, 500))
            ->set('form.frequency', $frequency = fake()->randomElement(MembershipFrequency::values()))
            ->set('form.description', $description = fake()->sentence())
            ->set('form.features', [fake()->randomElement(MembershipFeatures::names())])
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog-box');

        $this->assertEquals(MembershipInquiryStatus::RESOLVED, $membershipInquiry->refresh()->status);

        $this->assertDatabaseHas(Membership::class, [
            'company_id' => $membershipInquiry->company_id,
            'name' => $name,
            'price' => $price,
            'fee' => $fee,
            'e_letter_fee' => $e_letter_fee,
            'upload_accounts_limit' => $uploadAccountsLimit,
            'frequency' => $frequency,
            'description' => $description,
        ]);

        Mail::assertQueued(
            ResolveSpecialMembershipInquiryRequestMail::class,
            fn (ResolveSpecialMembershipInquiryRequestMail $mail) => $mail->assertTo($membershipInquiry->company->owner_email)
        );
    }

    #[Test]
    public function it_can_render_update_special_membership(): void
    {
        $membershipInquiry = MembershipInquiry::factory()->create(['status' => MembershipInquiryStatus::RESOLVED]);
        $membership = Membership::factory()
            ->create([
                'company_id' => $membershipInquiry->company_id,
                'status' => false,
            ]);

        Livewire::test(ViewPage::class, ['membershipInquiry' => $membershipInquiry])
            ->set('form.name', $name = fake()->word())
            ->set('form.price', $price = fake()->randomNumber(3))
            ->set('form.fee', $fee = fake()->numberBetween(1, 100))
            ->set('form.e_letter_fee', $e_letter_fee = fake()->randomFloat(2, 0.1, 25))
            ->set('form.upload_accounts_limit', $uploadAccountsLimit = fake()->numberBetween(1, 500))
            ->set('form.frequency', $frequency = fake()->randomElement(MembershipFrequency::values()))
            ->set('form.description', $description = fake()->sentence())
            ->set('form.features', [$features = fake()->randomElement(MembershipFeatures::names())])
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertDispatched('close-dialog-box');

        $this->assertEquals(MembershipInquiryStatus::RESOLVED, $membershipInquiry->refresh()->status);

        $this->assertDatabaseHas(Membership::class, [
            'id' => $membership->id,
            'company_id' => $membershipInquiry->company_id,
            'name' => $name,
            'price' => $price,
            'fee' => $fee,
            'e_letter_fee' => $e_letter_fee,
            'upload_accounts_limit' => $uploadAccountsLimit,
            'frequency' => $frequency,
            'description' => $description,
            'status' => true,
        ]);

        $this->assertEquals($membership->refresh()->features, [$features]);
    }
}
