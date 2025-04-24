<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipInquiries;

use App\Enums\MembershipFrequency;
use App\Enums\MembershipInquiryStatus;
use App\Livewire\Creditor\MembershipInquiries\Card;
use App\Mail\SpecialMembershipInquiryMail;
use App\Mail\SuperAdminMembershipInquiryMail;
use App\Models\CompanyMembership;
use App\Models\Membership;
use App\Models\MembershipInquiry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CardTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        Livewire::actingAs($this->user)
            ->test(Card::class)
            ->assertViewHas('membershipInquiryExists', false)
            ->assertViewIs('livewire.creditor.membership-inquiries.card')
            ->assertSee(__('Enterprise'))
            ->assertSee(__('Custom plans for enterprises that need to scale'))
            ->assertSee(__('Contact Us'))
            ->assertSee(__('Premium support'))
            ->assertSee(__('Dedicated Consumer Success Manager'))
            ->assertOk();
    }

    #[Test]
    public function it_can_seen_membership_inquiry_card(): void
    {
        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::YEARLY,
            'price' => 10,
        ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Card::class)
            ->assertSet('membershipInquiryExists', false)
            ->assertSee(__('Enterprise'))
            ->assertSee(__('Custom plans for enterprises that need to scale'))
            ->assertSee(__('Contact Us'))
            ->assertDontSee(__('"Your inquiry has been sent and you\'ll receive an update in 24 hours, you can reach out to help@younegotiate.com or +1847329847"'))
            ->assertOk();
    }

    #[Test]
    public function it_can_send_membership_inquiry(): void
    {
        Mail::fake();

        $membership = Membership::factory()->create([
            'status' => true,
            'frequency' => MembershipFrequency::YEARLY,
            'price' => 10,
        ]);

        CompanyMembership::factory()->create([
            'membership_id' => $membership->id,
            'company_id' => $this->user->company_id,
            'auto_renew' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(Card::class)
            ->set('inquiryForm.accounts_in_scope', $accountScope = 1000)
            ->set('inquiryForm.description', $description = fake()->sentence())
            ->call('membershipInquiry')
            ->assertOk()
            ->assertViewHas('membershipInquiryCreatedAt', fn ($createdAt) => $createdAt instanceof Carbon)
            ->assertDontSee(__('Enterprise'))
            ->assertDontSee(__('Custom plans for enterprises that need to scale'))
            ->assertDontSee(__('Contact Us'))
            ->assertSeeHtml(__('Your inquiry was sent to on <br>(<b>:date</b>)!', ['date' => now()->formatWithTimezone()]))
            ->assertSee(__('Please be on the lookout for a call or email from us AND check your account for a new custom membership plan within the next 24 hours.'))
            ->assertSee('🥂 ' . __('Cheers!') . ' 🥂');

        $this->assertDatabaseHas(MembershipInquiry::class, [
            'company_id' => $this->user->company_id,
            'status' => MembershipInquiryStatus::NEW_INQUIRY,
            'accounts_in_scope' => $accountScope,
            'description' => $description,
        ]);

        Mail::assertQueued(
            SpecialMembershipInquiryMail::class,
            fn (SpecialMembershipInquiryMail $mail) => $mail->assertTo($this->user->email)
        );

        Mail::assertQueued(SuperAdminMembershipInquiryMail::class);
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_send_membership_inquiry_validation(array $requestData, array $requestError): void
    {
        Livewire::actingAs($this->user)
            ->test(Card::class)
            ->set($requestData)
            ->call('membershipInquiry')
            ->assertOk()
            ->assertHasErrors($requestError);
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'inquiryForm.description' => '',
                ],
                ['inquiryForm.accounts_in_scope' => ['required']],
            ],
            [
                [
                    'inquiryForm.description' => 'Test Description',
                    'inquiryForm.accounts_in_scope' => 'Test',
                ],
                ['inquiryForm.accounts_in_scope' => ['integer']],
            ],
            [
                [
                    'inquiryForm.description' => 'Test Description',
                    'inquiryForm.accounts_in_scope' => 0,
                ],
                ['inquiryForm.accounts_in_scope' => ['gt:0']],
            ],
        ];
    }
}
