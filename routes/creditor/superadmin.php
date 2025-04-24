<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Middleware\Authenticate;
use App\Livewire\Creditor\AdminConfigurationPage;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedCommunicationHistory\ListPage as AutomatedCommunicationHistoryListPage;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\CreatePage as AutomatedTemplateCreatePage;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\EditPage as AutomatedTemplateEditPage;
use App\Livewire\Creditor\AutomatedCommunication\AutomatedTemplate\ListPage as AutomatedTemplateListPage;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\CreatePage as AutomationCampaignCreatePage;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\EditPage as AutomationCampaignEditPage;
use App\Livewire\Creditor\AutomatedCommunication\AutomationCampaign\ListPage as AutomationCampaignListPage;
use App\Livewire\Creditor\AutomatedCommunication\CommunicationStatus\ListPage as CommunicationStatusListPage;
use App\Livewire\Creditor\Communications\Campaign\IndexPage as CommunicationCampaignPage;
use App\Livewire\Creditor\Communications\CampaignTracker\ListPage as CommunicationCampaignTrackerPage;
use App\Livewire\Creditor\Communications\ELetter\IndexPage as CommunicationELetterPage;
use App\Livewire\Creditor\Communications\Group\IndexPage as CommunicationGroupPage;
use App\Livewire\Creditor\ManageCreditors\ListPage as ManageCreditorsListPage;
use App\Livewire\Creditor\ManageCreditors\ViewPage as ManageCreditorViewPage;
use App\Livewire\Creditor\ManageH2HUsers\ListPage as ManageH2HUsersListPage;
use App\Livewire\Creditor\ManagePartners\CreatePage as ManagePartnersCreatePage;
use App\Livewire\Creditor\ManagePartners\EditPage as ManagePartnersEditPage;
use App\Livewire\Creditor\ManagePartners\ListPage as ManagePartnersListPage;
use App\Livewire\Creditor\MembershipInquiries\ListPage as MembershipInquiriesListPage;
use App\Livewire\Creditor\Memberships\CreatePage as MembershipCreatePage;
use App\Livewire\Creditor\Memberships\EditPage as MembershipEditPage;
use App\Livewire\Creditor\Memberships\ListPage as MembershipListPage;
use App\Livewire\Creditor\Memberships\ShowPage as MembershipShowPage;
use App\Livewire\Creditor\Reports\ScheduleExport\CreatePage as ScheduleExportCreatePage;
use App\Livewire\Creditor\Reports\ScheduleExport\EditPage as ScheduleExportEditPage;
use App\Livewire\Creditor\Reports\ScheduleExport\ListPage as ScheduleExportListPage;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::prefix('super-admin')
    ->middleware([Authenticate::using('web'), RoleMiddleware::using(Role::SUPERADMIN->value)])
    ->group(function (): void {
        Route::get('configuration', AdminConfigurationPage::class)->name('super-admin.configurations');

        Route::prefix('manage-creditors')->group(function (): void {
            Route::get('/', ManageCreditorsListPage::class)->name('super-admin.manage-creditors');
            Route::get('{company}', ManageCreditorViewPage::class)->name('super-admin.manage-creditors.view');
        });

        Route::get('manage-users', ManageH2HUsersListPage::class)->name('super-admin.manage-h2h-users');
        Route::get('membership-inquiries', MembershipInquiriesListPage::class)->name('super-admin.membership-inquiries');

        Route::prefix('manage-memberships')->group(function (): void {
            Route::get('/', MembershipListPage::class)->name('super-admin.memberships');
            Route::get('create', MembershipCreatePage::class)->name('super-admin.memberships.create');
            Route::get('{membership}/edit', MembershipEditPage::class)->name('super-admin.memberships.edit');
            Route::get('{membership}', MembershipShowPage::class)->name('super-admin.memberships.show');
        });

        Route::prefix('automated-templates')->group(function (): void {
            Route::get('/', AutomatedTemplateListPage::class)->name('super-admin.automated-templates');
            Route::get('create', AutomatedTemplateCreatePage::class)->name('super-admin.automated-templates.create');
            Route::get('{automatedTemplate}/edit', AutomatedTemplateEditPage::class)->name('super-admin.automated-templates.edit');
        });

        Route::get('configure-communication', CommunicationStatusListPage::class)->name('super-admin.configure-communication-status');

        Route::prefix('automation-campaigns')->group(function (): void {
            Route::get('/', AutomationCampaignListPage::class)->name('super-admin.automation-campaigns');
            Route::get('create', AutomationCampaignCreatePage::class)->name('super-admin.automation-campaigns.create');
            Route::get('{automationCampaign}/edit', AutomationCampaignEditPage::class)->name('super-admin.automation-campaigns.edit');
        });

        Route::get('automated-communication-history', AutomatedCommunicationHistoryListPage::class)->name('super-admin.automated-communication-histories');

        Route::prefix('schedule-report')->group(function (): void {
            Route::get('/', ScheduleExportListPage::class)->name('schedule-export');
            Route::get('create', ScheduleExportCreatePage::class)->name('schedule-export.create');
            Route::get('{scheduleExport}/edit', ScheduleExportEditPage::class)->name('schedule-export.edit');
        });

        Route::prefix('manage-partners')->group(function (): void {
            Route::get('/', ManagePartnersListPage::class)->name('super-admin.manage-partners');
            Route::get('create', ManagePartnersCreatePage::class)->name('super-admin.manage-partners.create');
            Route::get('{partner}/edit', ManagePartnersEditPage::class)->name('super-admin.manage-partners.edit');
        });

        Route::prefix('communications')->group(function (): void {
            Route::get('templates', CommunicationELetterPage::class)->name('super-admin.communication.templates');
            Route::get('groups', CommunicationGroupPage::class)->name('super-admin.communication.groups');
            Route::get('campaign', CommunicationCampaignPage::class)->name('super-admin.communication.campaigns');
            Route::get('campaign-tracker', CommunicationCampaignTrackerPage::class)->name('super-admin.communication.campaign-trackers');
        });
    });
