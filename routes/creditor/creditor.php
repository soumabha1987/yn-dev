<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Middleware\ActivePlan;
use App\Http\Middleware\ActiveUser;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckProfileCompleted;
use App\Livewire\Creditor\AboutUs\CreateOrUpdatePage as AboutUsCreateOrUpdatePage;
use App\Livewire\Creditor\AccountProfile\IndexPage as AccountProfilePage;
use App\Livewire\Creditor\AccountSettingsPage;
use App\Livewire\Creditor\BillingHistoryPage;
use App\Livewire\Creditor\CFPBRegisterPage;
use App\Livewire\Creditor\Communications\Campaign\IndexPage as CommunicationCampaignPage;
use App\Livewire\Creditor\Communications\CampaignTracker\ListPage as CommunicationCampaignTrackerPage;
use App\Livewire\Creditor\Communications\ELetter\IndexPage as CommunicationELetterPage;
use App\Livewire\Creditor\Communications\Group\IndexPage as CommunicationGroupPage;
use App\Livewire\Creditor\ConsumerOffers\Page as ConsumerOffersPage;
use App\Livewire\Creditor\ConsumerPayTerms\ListPage as ConsumerPayTermsListPage;
use App\Livewire\Creditor\Dashboard\CompletedNegotiations;
use App\Livewire\Creditor\Dashboard\DisputeReports;
use App\Livewire\Creditor\Dashboard\FailedPayments;
use App\Livewire\Creditor\Dashboard\OpenNegotiations;
use App\Livewire\Creditor\Dashboard\RecentlyCompletedNegotiations;
use App\Livewire\Creditor\Dashboard\RecentTransaction;
use App\Livewire\Creditor\Dashboard\Stats\FailedTransactionPage;
use App\Livewire\Creditor\Dashboard\Stats\PaymentForecastPage;
use App\Livewire\Creditor\Dashboard\Stats\PaymentPlanPage;
use App\Livewire\Creditor\Dashboard\Stats\SuccessfulTransactionPage;
use App\Livewire\Creditor\Dashboard\UpcomingTransaction;
use App\Livewire\Creditor\ImportConsumers\FileUploadHistoryPage;
use App\Livewire\Creditor\ImportConsumers\IndexPage as ImportConsumerPage;
use App\Livewire\Creditor\ImportConsumers\MapUploadedFilePage;
use App\Livewire\Creditor\ImportConsumers\UploadFilePage;
use App\Livewire\Creditor\MembershipSettings\Page as MembershipSettingsPage;
use App\Livewire\Creditor\MerchantSettingsPage;
use App\Livewire\Creditor\PayTerms\CreatePage as PayTermsCreatePage;
use App\Livewire\Creditor\PayTerms\EditPage as PayTermsEditPage;
use App\Livewire\Creditor\PayTerms\ListPage as PayTermsListPage;
use App\Livewire\Creditor\PersonalizedLogoPage;
use App\Livewire\Creditor\Reports\ScheduleExport\CreatePage as ScheduleExportCreatePage;
use App\Livewire\Creditor\Reports\ScheduleExport\EditPage as ScheduleExportEditPage;
use App\Livewire\Creditor\Reports\ScheduleExport\ListPage as ScheduleExportListPage;
use App\Livewire\Creditor\SetupWizardPage;
use App\Livewire\Creditor\SftpConnection\CreatePage as SftpConnectionCreatePage;
use App\Livewire\Creditor\SftpConnection\EditPage as SftpConnectionEditPage;
use App\Livewire\Creditor\SftpConnection\ListPage as SftpConnectionListPage;
use App\Livewire\Creditor\TermsAndConditions\IndexPage as TermsAndConditionsIndexPage;
use App\Livewire\Creditor\Users\CreatePage as UserCreatePage;
use App\Livewire\Creditor\Users\EditPage as UserEditPage;
use App\Livewire\Creditor\Users\ListPage as UserListPage;
use App\Livewire\Creditor\VideoTutorialPage;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::middleware([
    Authenticate::class,
    ActiveUser::class,
    EnsureEmailIsVerified::redirectTo('email-verification-notice'),
    RoleMiddleware::using(Role::CREDITOR->value),
])->group(function (): void {
    Route::get('profile', AccountProfilePage::class)->name('creditor.profile');

    Route::middleware(CheckProfileCompleted::class)->group(function (): void {
        Route::get('billing-history', BillingHistoryPage::class)
            ->name('creditor.billing-history');

        Route::get('membership-settings', MembershipSettingsPage::class)
            ->name('creditor.membership-settings');

        Route::middleware(ActivePlan::class)->group(function (): void {
            Route::get('account-settings', AccountSettingsPage::class)->name('creditor.settings');
            Route::get('merchant-settings', MerchantSettingsPage::class)->name('creditor.merchant-settings');

            Route::prefix('dashboard')->group(function (): void {
                Route::get('open-negotiations', OpenNegotiations::class)->name('creditor.dashboard');
                Route::get('recent-transactions', RecentTransaction::class)->name('creditor.dashboard.recent-transactions');
                Route::get('failed-payments', FailedPayments::class)->name('creditor.dashboard.failed-payments');
                Route::get('upcoming-transactions', UpcomingTransaction::class)->name('creditor.dashboard.upcoming-transactions');
                Route::get('reported-disputes', DisputeReports::class)->name('creditor.dashboard.dispute-reports');
                Route::get('completed-negotiations', CompletedNegotiations::class)->name('creditor.dashboard.completed-negotiations');
                Route::get('recently-completed-negotiations', RecentlyCompletedNegotiations::class)->name('creditor.dashboard.recently-completed-negotiations');
            });

            Route::get('payment-plan', PaymentPlanPage::class)->name('creditor.dashboard.payment-plan');
            Route::get('payment-forecast', PaymentForecastPage::class)->name('creditor.dashboard.payment-forecast');
            Route::get('successful-transaction', SuccessfulTransactionPage::class)->name('creditor.dashboard.successful-transaction');
            Route::get('failed-transaction', FailedTransactionPage::class)->name('creditor.dashboard.failed-transaction');

            Route::get('setup', SetupWizardPage::class)->name('creditor.setup-wizard');
            Route::get('video-tutorial', VideoTutorialPage::class)->name('creditor.video-tutorial');

            Route::get('consumer-offers', ConsumerOffersPage::class)->name('creditor.consumer-offers');

            Route::get('consumer-pay-terms', ConsumerPayTermsListPage::class)->name('creditor.consumer-pay-terms');

            Route::prefix('import-consumers')
                ->group(function (): void {
                    Route::get('upload-header-file', UploadFilePage::class)->name('creditor.import-consumers.upload-file');
                    Route::get('map-uploaded-file/{csvHeader}', MapUploadedFilePage::class)->name('creditor.import-consumers.upload-file.map');
                    Route::get('/', ImportConsumerPage::class)->name('creditor.import-consumers.index');
                    Route::get('file-upload-histories', FileUploadHistoryPage::class)
                        ->name('creditor.import-consumers.file-upload-history');
                });

            Route::prefix('pay-terms')->group(function (): void {
                Route::get('/', PayTermsListPage::class)->name('creditor.pay-terms');
                Route::get('create', PayTermsCreatePage::class)->name('creditor.pay-terms.create');
                Route::get('edit/{id}/{payTerms}', PayTermsEditPage::class)
                    ->where(['payTerms' => 'master-terms|sub-account-terms|group-terms'])
                    ->name('creditor.pay-terms.edit');
            });

            Route::prefix('sftp')->group(function (): void {
                Route::get('/', SftpConnectionListPage::class)->name('creditor.sftp');
                Route::get('create', SftpConnectionCreatePage::class)->name('creditor.sftp.create');
                Route::get('{sftp}/edit', SftpConnectionEditPage::class)->name('creditor.sftp.edit');
            });

            Route::get('personalized-logo-and-link', PersonalizedLogoPage::class)->name('creditor.personalized-logo-and-link');

            Route::prefix('users')->group(function (): void {
                Route::get('/', UserListPage::class)->name('creditor.users');
                Route::get('create', UserCreatePage::class)->name('creditor.users.create');
                Route::get('edit/{user}', UserEditPage::class)->name('creditor.users.edit');
            });

            Route::get('terms-conditions', TermsAndConditionsIndexPage::class)->name('creditor.terms-conditions');

            Route::prefix('about-us')->group(function (): void {
                Route::get('/', AboutUsCreateOrUpdatePage::class)->name('creditor.about-us.create-or-update');
            });

            Route::prefix('schedule-report')->group(function (): void {
                Route::get('/', ScheduleExportListPage::class)->name('creditor.schedule-export');
                Route::get('create', ScheduleExportCreatePage::class)->name('creditor.schedule-export.create');
                Route::get('{scheduleExport}/edit', ScheduleExportEditPage::class)->name('creditor.schedule-export.edit');
            });

            Route::prefix('communications')->group(function (): void {
                Route::get('e-letter', CommunicationELetterPage::class)->name('creditor.communication.e-letters');
                Route::get('cfpb-template', CFPBRegisterPage::class)->name('creditor.cfpb-communication');
                Route::get('campaign', CommunicationCampaignPage::class)->name('creditor.communication.campaigns');
                Route::get('groups', CommunicationGroupPage::class)->name('creditor.communication.groups');
                Route::get('campaign-tracker', CommunicationCampaignTrackerPage::class)->name('creditor.communication.campaign-trackers');
            });
        });
    });
});
