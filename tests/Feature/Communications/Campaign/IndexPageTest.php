<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\Campaign;

use App\Enums\CampaignFrequency;
use App\Enums\CompanyStatus;
use App\Enums\ConsumerStatus;
use App\Enums\CreditorCurrentStep;
use App\Enums\CustomContentType;
use App\Enums\GroupConsumerState;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Enums\TemplateType;
use App\Jobs\ProcessCampaignConsumersJob;
use App\Livewire\Creditor\Communications\Campaign\IndexPage;
use App\Models\Campaign;
use App\Models\CampaignTracker;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Consumer;
use App\Models\CsvHeader;
use App\Models\CustomContent;
use App\Models\Group;
use App\Models\MembershipPaymentProfile;
use App\Models\Merchant;
use App\Models\Template;
use App\Models\User;
use App\Models\YnTransaction;
use App\Services\TilledPaymentService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class IndexPageTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->user->update(['subclient_id' => null]);

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);
        $this->user->assignRole($role);

        $this->company->update([
            'status' => CompanyStatus::ACTIVE,
            'current_step' => CreditorCurrentStep::COMPLETED,
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
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'verified_at' => now(),
            ]);

        CsvHeader::query()
            ->create([
                'subclient_id' => null,
                'company_id' => $this->user->company_id,
                'is_mapped' => true,
            ]);
    }

    #[Test]
    public function it_can_render_livewire_component(): void
    {
        $this->get(route('creditor.communication.campaigns'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_not_completed_setup_wizard(): void
    {
        $company = Company::factory()->create();

        $this->user->update(['company_id' => $company->id]);

        $this->get(route('creditor.communication.campaigns'))
            ->assertDontSeeLivewire(IndexPage::class)
            ->assertRedirectToRoute('creditor.profile')
            ->assertStatus(302);
    }

    #[Test]
    public function it_can_ignore_completed_setup_wizard_when_role_super_admin(): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $this->actingAs($user)
            ->get(route('super-admin.communication.campaigns'))
            ->assertSeeLivewire(IndexPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_livewire_campaign_view_page(): void
    {
        Livewire::test(IndexPage::class)
            ->assertViewIs('livewire.creditor.communications.campaign.index-page')
            ->assertSee(__('Schedule Campaign'))
            ->assertOk();
    }

    #[Test]
    public function it_can_create_campaign_when_frequency_once_when_super_admin(): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $template = Template::factory()->create([
            'company_id' => $user->company_id,
            'type' => $type = fake()->randomElement([TemplateType::SMS->value, TemplateType::EMAIL->value]),
        ]);

        $group = Group::factory()->create([
            'company_id' => $user->company_id,
        ]);

        Livewire::actingAs($user)
            ->test(IndexPage::class)
            ->assertSet('form.is_run_immediately', false)
            ->set('form.type', $type)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::ONCE)
            ->set('form.start_date', $startDate = today()->addDays(fake()->numberBetween(1, 350))->toDateString())
            ->set('form.end_date', null)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Campaign created.'));

        Notification::assertNotNotified(__('This campaign update successfully.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => $startDate,
            'end_date' => null,
            'day_of_week' => null,
            'day_of_month' => null,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_create_campaign_when_frequency_once(): void
    {
        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(IndexPage::class)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::ONCE)
            ->set('form.start_date', $startDate = today()->addDays(fake()->numberBetween(1, 350))->toDateString())
            ->set('form.end_date', null)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Campaign created.'));

        Notification::assertNotNotified(__('This campaign update successfully.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => $startDate,
            'end_date' => null,
            'day_of_week' => null,
            'day_of_month' => null,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_create_campaign_when_frequency_daily(): void
    {
        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(IndexPage::class)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::DAILY)
            ->set('form.start_date', $startDate = today()->addDays($startNumber = fake()->numberBetween(1, 100))->toDateString())
            ->set('form.end_date', $endDate = today()->addDays(fake()->numberBetween($startNumber, 350))->toDateString())
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertSet('form.end_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Campaign created.'));

        Notification::assertNotNotified(__('This campaign update successfully.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::DAILY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'day_of_week' => null,
            'day_of_month' => null,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_create_campaign_when_frequency_weekly(): void
    {
        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(IndexPage::class)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::WEEKLY)
            ->set('form.start_date', $startDate = today()->addDays($startNumber = fake()->numberBetween(1, 100))->toDateString())
            ->set('form.end_date', $endDate = today()->addDays(fake()->numberBetween($startNumber, 350))->toDateString())
            ->set('form.day_of_week', $dayOfWeek = fake()->numberBetween(0, 6))
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertSet('form.end_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Campaign created.'));

        Notification::assertNotNotified(__('This campaign update successfully.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::WEEKLY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'day_of_week' => $dayOfWeek,
            'day_of_month' => null,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_create_campaign_when_frequency_monthly(): void
    {
        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Livewire::test(IndexPage::class)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::MONTHLY)
            ->set('form.start_date', $startDate = today()->addDays($startNumber = fake()->numberBetween(1, 100))->toDateString())
            ->set('form.end_date', $endDate = today()->addDays(fake()->numberBetween($startNumber, 350))->toDateString())
            ->set('form.day_of_month', $dayOfMonth = fake()->numberBetween(1, 31))
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertSet('form.end_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('Campaign created.'));

        Notification::assertNotNotified(__('This campaign update successfully.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::MONTHLY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'day_of_week' => null,
            'day_of_month' => $dayOfMonth,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_update_campaign(): void
    {
        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        $campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(IndexPage::class)
            ->set('form.campaign_id', $campaign->id)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::MONTHLY)
            ->set('form.start_date', $startDate = today()->addDays($startNumber = fake()->numberBetween(1, 100))->toDateString())
            ->set('form.end_date', $endDate = today()->addDays(fake()->numberBetween($startNumber, 350))->toDateString())
            ->set('form.day_of_month', $dayOfMonth = fake()->numberBetween(1, 31))
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', '')
            ->assertSet('form.end_date', '')
            ->assertDispatched('refresh-list-view');

        Notification::assertNotified(__('This campaign update successfully.'));

        Notification::assertNotNotified(__('Campaign created.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::MONTHLY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'day_of_week' => null,
            'day_of_month' => $dayOfMonth,
            'is_run_immediately' => false,
        ]);
    }

    #[Test]
    public function it_can_not_update_other_companies_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        Livewire::test(IndexPage::class)
            ->call('edit', $campaign->id)
            ->assertOk()
            ->assertSet('form.template_id', null)
            ->assertSet('form.group_id', null)
            ->assertSet('form.frequency', '')
            ->assertSet('form.start_date', today()->addDay()->toDateString())
            ->assertSet('form.end_date', '')
            ->assertNotDispatched('refresh-list-view');

        Notification::assertNotified(__('Sorry you can not edit this campaign.'));
    }

    #[Test]
    public function it_can_call_edit_set_campaign_data(): void
    {
        $campaign = Campaign::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(IndexPage::class)
            ->call('edit', $campaign->id)
            ->assertOk()
            ->assertSet('form.campaign_id', $campaign->id)
            ->assertSet('form.template_id', $campaign->template_id)
            ->assertSet('form.group_id', $campaign->group_id)
            ->assertSet('form.frequency', $campaign->frequency->value)
            ->assertSet('form.start_date', $campaign->start_date->toDateString())
            ->assertSet('form.end_date', $campaign->end_date->toDateString())
            ->assertSet('form.day_of_week', $campaign->day_of_week)
            ->assertSet('form.day_of_month', $campaign->day_of_month)
            ->assertNotDispatched('refresh-list-view');

        Notification::assertNotNotified(__('Sorry you can not edit this campaign.'));
    }

    #[Test]
    #[DataProvider('requestValidation')]
    public function it_can_createOrUpdate_campaign_validation(array $requestData, array $requestError): void
    {
        Livewire::test(IndexPage::class)
            ->set($requestData)
            ->call('createOrUpdate')
            ->assertOk()
            ->assertHasErrors($requestError)
            ->assertNotDispatched('refresh-list-view');
    }

    #[Test]
    public function it_can_real_time_create_campaign(): void
    {
        Queue::fake();

        $template = Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        $consumers = Consumer::factory($processedCount = 10)
            ->create([
                'company_id' => $this->user->company_id,
                'status' => ConsumerStatus::UPLOADED,
            ]);

        $group = Group::factory()->create([
            'company_id' => $this->user->company_id,
            'consumer_state' => GroupConsumerState::ALL_ACTIVE,
        ]);

        $companyMembership = CompanyMembership::factory()->create(['company_id' => $this->user->company_id]);

        $ecoMailAmount = (float) $companyMembership->membership->e_letter_fee;

        $this->partialMock(TilledPaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPaymentIntents')
                ->withAnyArgs()
                ->andReturn(['status' => 'succeeded']);
        });

        MembershipPaymentProfile::factory()->create(['company_id' => $this->user->company_id]);

        Livewire::test(IndexPage::class)
            ->set('form.template_id', $template->id)
            ->set('form.group_id', $group->id)
            ->set('form.frequency', CampaignFrequency::ONCE)
            ->set('group', $group)
            ->call('createImmediately')
            ->assertOk()
            ->assertDispatched('refresh-list-view');

        Queue::assertPushed(ProcessCampaignConsumersJob::class, 1);

        Notification::assertNotified(__('Your campaign has been successfully created and is now being processed.'));

        $this->assertDatabaseHas(Campaign::class, [
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'template_id' => $template->id,
            'group_id' => $group->id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => today()->toDateString(),
            'end_date' => null,
            'day_of_week' => null,
            'day_of_month' => null,
            'is_run_immediately' => true,
        ]);

        $this->assertDatabaseHas(YnTransaction::class, [
            'company_id' => $this->user->company_id,
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

        $this->assertDatabaseHas(CampaignTracker::class, [
            'consumer_count' => $processedCount,
            'total_balance_of_consumers' => $consumers->sum('current_balance'),
        ]);
    }

    #[Test]
    public function it_can_update_display_one_time_campaign_start_date_was_past_date(): void
    {
        Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $this->user->company_id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => today()->subDay(),
        ]);

        Livewire::test(IndexPage::class)
            ->call('edit', $campaign)
            ->assertSet('form.campaign_id', $campaign->id)
            ->assertSet('form.template_id', $campaign->template_id)
            ->assertSet('form.group_id', $campaign->group_id)
            ->assertSet('form.frequency', CampaignFrequency::ONCE->value)
            ->assertSet('form.start_date', today()->toDateString())
            ->assertSet('openCampaignDialog', false)
            ->assertSet('form.is_run_immediately', true)
            ->assertOk();
    }

    #[Test]
    public function it_can_update_display_one_time_campaign_start_date_was_future_date(): void
    {
        Template::factory()->create([
            'company_id' => $this->user->company_id,
            'type' => TemplateType::E_LETTER,
        ]);

        Group::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        $campaign = Campaign::factory()->create([
            'company_id' => $this->user->company_id,
            'frequency' => CampaignFrequency::ONCE,
            'start_date' => today()->addDay(),
        ]);

        Livewire::test(IndexPage::class)
            ->call('edit', $campaign)
            ->assertSet('form.campaign_id', $campaign->id)
            ->assertSet('form.template_id', $campaign->template_id)
            ->assertSet('form.group_id', $campaign->group_id)
            ->assertSet('form.frequency', CampaignFrequency::ONCE->value)
            ->assertSet('form.start_date', today()->addDay()->toDateString())
            ->assertSet('openCampaignDialog', false)
            ->assertSet('form.is_run_immediately', false)
            ->assertOk();
    }

    public static function requestValidation(): array
    {
        return [
            [
                [
                    'form.frequency' => '',
                    'form.start_date' => '',
                ],
                [
                    'form.template_id' => ['required'],
                    'form.group_id' => ['required'],
                    'form.frequency' => ['required'],
                    'form.start_date' => ['required'],
                ],
            ],
            [
                [
                    'form.template_id' => fake()->word(),
                    'form.group_id' => fake()->word(),
                    'form.frequency' => fake()->word(),
                    'form.start_date' => fake()->date(max: today()),
                ],
                [
                    'form.template_id' => ['integer'],
                    'form.group_id' => ['integer'],
                    'form.frequency' => ['in'],
                    'form.start_date' => ['after_or_equal:' . today()->addDay()->toDateString()],
                ],
            ],
            [
                [
                    'form.template_id' => 123,
                    'form.group_id' => 123,
                    'form.frequency' => CampaignFrequency::ONCE->value,
                    'form.start_date' => today()->addYears(2)->toDateString(),
                ],
                [
                    'form.template_id' => ['exists'],
                    'form.group_id' => ['exists'],
                    'form.start_date' => ['before_or_equal:' . today()->addYear()->toDateString()],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::DAILY->value,
                    'form.start_date' => today()->toDateString(),
                ],
                [
                    'form.end_date' => ['required'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::MONTHLY->value,
                    'form.start_date' => today()->toDateString(),
                    'form.end_date' => today()->subDay(),
                ],
                [
                    'form.end_date' => ['after_or_equal:today'],
                    'form.day_of_month' => ['required'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::WEEKLY->value,
                    'form.start_date' => today()->toDateString(),
                    'form.end_date' => today()->addDays(500),
                ],
                [
                    'form.end_date' => ['before_or_equal:' . today()->addYear()->toDateString()],
                    'form.day_of_week' => ['required'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::WEEKLY->value,
                    'form.day_of_week' => fake()->word(),
                ],
                [
                    'form.day_of_week' => ['integer'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::WEEKLY->value,
                    'form.day_of_week' => 7,
                ],
                [
                    'form.day_of_week' => ['in'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::MONTHLY->value,
                    'form.day_of_month' => fake()->word(),
                ],
                [
                    'form.day_of_month' => ['integer'],
                ],
            ],
            [
                [
                    'form.frequency' => CampaignFrequency::MONTHLY->value,
                    'form.day_of_month' => fake()->numberBetween(32, 100),
                ],
                [
                    'form.day_of_month' => ['max:31'],
                ],
            ],
        ];
    }
}
