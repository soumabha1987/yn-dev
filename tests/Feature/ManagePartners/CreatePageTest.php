<?php

declare(strict_types=1);

namespace Tests\Feature\ManagePartners;

use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\ManagePartners\CreatePage;
use App\Models\Partner;
use App\Rules\MultipleEmails;
use App\Rules\NamingRule;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class CreatePageTest extends AuthTestCase
{
    #[Test]
    public function access_forbidden_for_non_super_admin_user(): void
    {
        $this->get(route('super-admin.manage-partners.create'))
            ->assertDontSeeLivewire(CreatePage::class)
            ->assertForbidden();
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $this->user->assignRole($role);

        $this->user->company->update(['is_super_admin_company' => true]);

        $this->get(route('super-admin.manage-partners.create'))
            ->assertSeeLivewire(CreatePage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_livewire_view_page(): void
    {
        Livewire::test(CreatePage::class)
            ->assertViewIs('livewire.creditor.manage-partners.create-page')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_create_partner_validation(array $requestSetData, array $requestErrors): void
    {
        Partner::factory()->create(['name' => 'test']);

        Livewire::test(CreatePage::class)
            ->set($requestSetData)
            ->call('create')
            ->assertOk()
            ->assertHasErrors($requestErrors)
            ->assertNoRedirect();
    }

    #[Test]
    public function it_can_create_partner(): void
    {
        Livewire::test(CreatePage::class)
            ->set('form.name', $name = fake()->name())
            ->set('form.contact_first_name', $fname = fake()->firstName())
            ->set('form.contact_last_name', $lname = fake()->lastName())
            ->set('form.contact_email', $email = fake()->safeEmail())
            ->set('form.contact_phone', $phone = '9008990067')
            ->set('form.revenue_share', $revenueShare = fake()->numberBetween(0, 99))
            ->set('form.creditors_quota', $quota = fake()->numberBetween(100, 999999))
            ->set('form.report_emails', 'a@a.com')
            ->call('create')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.manage-partners'));

        Notification::assertNotified(__('Partner link created!'));

        $this->assertDatabaseHas(Partner::class, [
            'name' => $name,
            'contact_first_name' => $fname,
            'contact_last_name' => $lname,
            'contact_email' => $email,
            'contact_phone' => $phone,
            'revenue_share' => $revenueShare,
            'creditors_quota' => $quota,
        ]);
    }

    public static function requestValidation(): array
    {
        return [
            [
                [],
                [
                    'form.name' => ['required'],
                    'form.contact_first_name' => ['required'],
                    'form.contact_last_name' => ['required'],
                    'form.contact_email' => ['required'],
                    'form.contact_phone' => ['required'],
                    'form.revenue_share' => ['required'],
                    'form.creditors_quota' => ['required'],
                    'form.report_emails' => ['required'],
                ],
            ],
            [
                [
                    'form.name' => str('a')->repeat(51),
                    'form.contact_first_name' => str('a')->repeat(51),
                    'form.contact_last_name' => str('a')->repeat(51),
                    'form.contact_email' => str('a')->repeat(51),
                    'form.contact_phone' => str('a')->repeat(51),
                    'form.revenue_share' => str('a')->repeat(51),
                    'form.creditors_quota' => str('a')->repeat(51),
                    'form.report_emails' => str('a')->repeat(51),
                ],
                [
                    'form.name' => ['max:50'],
                    'form.contact_first_name' => ['max:50'],
                    'form.contact_last_name' => ['max:50'],
                    'form.contact_email' => ['max:50'],
                    'form.contact_phone' => ['phone:US'],
                    'form.revenue_share' => ['numeric'],
                    'form.creditors_quota' => ['integer'],
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
            [
                [
                    'form.name' => 'a',
                    'form.contact_first_name' => 'a',
                    'form.contact_last_name' => 'a',
                    'form.contact_email' => 'a',
                    'form.contact_phone' => 'a',
                    'form.revenue_share' => 'a',
                    'form.creditors_quota' => 'a',
                    'form.report_emails' => 'a',
                ],
                [
                    'form.name' => ['min:3'],
                    'form.contact_first_name' => ['min:3'],
                    'form.contact_last_name' => ['min:3'],
                    'form.contact_email' => ['min:3'],
                    'form.contact_phone' => ['phone:US'],
                    'form.revenue_share' => ['numeric'],
                    'form.creditors_quota' => ['integer'],
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
            [
                [
                    'form.name' => 'abc abc   abc',
                    'form.contact_first_name' => 'abc@@@',
                    'form.contact_last_name' => 'xyz,,,tim###',
                    'form.contact_email' => 'abcdef',
                    'form.contact_phone' => '9090909090',
                    'form.revenue_share' => 101,
                    'form.creditors_quota' => '-0',
                    'form.report_emails' => 'abcdef',
                ],
                [
                    'form.name' => [NamingRule::class],
                    'form.contact_first_name' => [NamingRule::class],
                    'form.contact_last_name' => [NamingRule::class],
                    'form.contact_email' => ['email'],
                    'form.contact_phone' => ['phone:US'],
                    'form.revenue_share' => ['max:99'],
                    'form.creditors_quota' => ['regex'],
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
            [
                [
                    'form.name' => 'test',
                    'form.report_emails' => 'a@a.com, b@b.com, cccccc',
                ],
                [
                    'form.name' => ['unique'],
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
            [
                [
                    'form.report_emails' => 'a@a.com, b@b.com, c@c.com, d@d.com, e@e.com, f@f.com',
                ],
                [
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
            [
                [
                    'form.report_emails' => ',,,,,,',
                ],
                [
                    'form.report_emails' => [MultipleEmails::class],
                ],
            ],
        ];
    }
}
