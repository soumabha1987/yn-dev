<?php

declare(strict_types=1);

namespace Tests\Feature\MembershipInquiries;

use App\Enums\MembershipInquiryStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\MembershipInquiries\ListPage;
use App\Livewire\Creditor\MembershipInquiries\ViewPage;
use App\Mail\CloseSpecialMembershipInquiryMail;
use App\Models\Company;
use App\Models\MembershipInquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPageTest extends TestCase
{
    #[Test]
    public function it_can_render_membership_inquiries_list_page_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $user = User::factory()->create();

        $user->assignRole($role);

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('super-admin.membership-inquiries'))
            ->assertSeeLivewire(ListPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_component_with_correct_view(): void
    {
        $membershipInquiry = MembershipInquiry::factory()->create();

        Livewire::test(ListPage::class)
            ->assertViewIs('livewire.creditor.membership-inquiries.list-page')
            ->assertSeeLivewire(ViewPage::class)
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $membershipInquiry->is($membershipInquiries->getCollection()->first())
            )
            ->assertSee(str($membershipInquiry->company->company_name)->title())
            ->assertSee($membershipInquiry->company->owner_email)
            ->assertSee($membershipInquiry->company->owner_phone)
            ->assertSee($membershipInquiry->created_at->formatWithTimezone())
            ->assertOk();
    }

    #[Test]
    public function it_can_render_deleted_company_membership_inquiry_not_display(): void
    {
        $membershipInquiry = MembershipInquiry::factory()
            ->for(Company::factory()->create(['deleted_at' => now()]))
            ->create();

        Livewire::test(ListPage::class)
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $membershipInquiries->isEmpty()
            )
            ->assertDontSee($membershipInquiry->created_at->format('M d, Y'))
            ->assertOk();
    }

    #[Test]
    public function it_can_close_special_membership_request(): void
    {
        Mail::fake();

        $membershipInquiry = MembershipInquiry::factory()
            ->for(Company::factory()->create())
            ->create();

        Livewire::test(ListPage::class)
            ->call('closeInquiry', $membershipInquiry->id)
            ->assertOk();

        $this->assertEquals(MembershipInquiryStatus::CLOSE, $membershipInquiry->refresh()->status);

        Mail::assertQueued(
            CloseSpecialMembershipInquiryMail::class,
            fn (CloseSpecialMembershipInquiryMail $mail) => $mail->assertTo($membershipInquiry->company->owner_email)
        );
    }

    #[Test]
    #[DataProvider('filters')]
    public function it_can_view_membership_inquiries(array $urlParam, string $seenCompanyName, string $unSeenCompanyName): void
    {
        MembershipInquiry::factory()
            ->forEachSequence(
                [
                    'status' => MembershipInquiryStatus::RESOLVED,
                    'company_id' => Company::factory()->create([
                        'company_name' => 'read company_a',
                        'owner_email' => 'company_a@test.com',
                        'owner_phone' => '9090909090',
                    ])->id,
                ],
                [
                    'status' => MembershipInquiryStatus::NEW_INQUIRY,
                    'company_id' => Company::factory()->create([
                        'company_name' => 'un read company_b',
                        'owner_email' => 'company_b@test.com',
                        'owner_phone' => '8080808080',
                    ])->id,
                ]
            )
            ->create();

        Livewire::withUrlParams($urlParam)
            ->test(ListPage::class)
            ->assertSee(str($seenCompanyName)->title())
            ->assertDontSee(str($unSeenCompanyName)->title())
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_name(string $direction): void
    {
        $createdMembershipInquiries = MembershipInquiry::factory(22)
            ->sequence(fn (Sequence $sequence) => [
                'company_id' => Company::factory()
                    ->state([
                        'company_name' => range('A', 'Z')[$sequence->index],
                    ]),
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'company_name',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'company_name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $direction === 'ASC'
                    ? $createdMembershipInquiries->first()->is($membershipInquiries->getCollection()->first())
                    : $createdMembershipInquiries->last()->is($membershipInquiries->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_email(string $direction): void
    {
        $createdMembershipInquiries = MembershipInquiry::factory(4)
            ->sequence(fn (Sequence $sequence) => [
                'company_id' => Company::factory()
                    ->state([
                        'owner_email' => range('A', 'Z')[$sequence->index] . '@gmail.com',
                    ]),
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'email',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'email')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $direction === 'ASC'
                    ? $createdMembershipInquiries->first()->is($membershipInquiries->getCollection()->first())
                    : $createdMembershipInquiries->last()->is($membershipInquiries->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_company_phone(string $direction): void
    {
        $createdMembershipInquiries = MembershipInquiry::factory(4)
            ->sequence(fn (Sequence $sequence) => [
                'company_id' => Company::factory()
                    ->state([
                        'owner_phone' => '484999000' . $sequence->index,
                    ]),
            ])
            ->create();

        Livewire::withQueryParams([
            'sort' => 'phone',
            'direction' => $direction === 'ASC',
        ])
            ->test(ListPage::class)
            ->assertOk()
            ->assertSet('sortCol', 'phone')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $direction === 'ASC'
                    ? $createdMembershipInquiries->first()->is($membershipInquiries->getCollection()->first())
                    : $createdMembershipInquiries->last()->is($membershipInquiries->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_order_by_status(string $direction): void
    {
        $createdMembershipInquiries = MembershipInquiry::factory(3)
            ->for(Company::factory()->create())
            ->sequence(
                ['status' => MembershipInquiryStatus::NEW_INQUIRY],
                ['status' => MembershipInquiryStatus::RESOLVED],
                ['status' => MembershipInquiryStatus::CLOSE],
            )
            ->create();

        Livewire::test(ListPage::class)
            ->assertOk()
            ->set('sortCol', 'status')
            ->set('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'membershipInquiries',
                fn (LengthAwarePaginator $membershipInquiries) => $direction === 'ASC'
                    ? $createdMembershipInquiries->first()->is($membershipInquiries->getCollection()->first())
                    : $createdMembershipInquiries->last()->is($membershipInquiries->getCollection()->first())
            );
    }

    public static function filters(): array
    {
        return [
            [
                ['search' => 'read company_a'],
                'read company_a',
                'un read company_b',
            ],
            [
                ['search' => 'company_a@test.com'],
                'read company_a',
                'un read company_b',
            ],
            [
                ['search' => '8080808080'],
                'un read company_b',
                'read company_a',
            ],
        ];
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}
