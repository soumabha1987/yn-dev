<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\Role;
use App\Models\User;
use App\Services\ConsumerService;
use App\Services\MembershipInquiryService;
use App\Services\SetupWizardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SidebarComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        /** @var ?User $user */
        $user = Auth::user();

        if ($user) {
            $user->loadMissing(['roles:id,name', 'company']);

            $membershipInquiryCount = Cache::remember('new_inquires_count', now()->addHour(), fn () => app(MembershipInquiryService::class)->newInquiresCount());
            $newOfferCount = Cache::remember('new_offer_count_' . $user->company_id, now()->addHour(), fn () => app(ConsumerService::class)->getCountOfNewOffer($user->company_id));

            $sidebarMenu = match (true) {
                $user->hasRole(Role::SUPERADMIN) => $this->superAdminSidebarMenus($membershipInquiryCount),
                $user->hasRole(Role::CREDITOR) => $this->sidebarMenus($user, $newOfferCount),
                default => [],
            };

            $view->with('sidebarMenu', $sidebarMenu);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function superAdminSidebarMenus(int $membershipInquiryCount): array
    {
        return [
            [
                'title' => __('Manage Creditors'),
                'icon' => 'heroicon-o-user-group',
                'route_name' => 'super-admin.manage-creditors',
            ],
            [
                'title' => __('Manage H2H Users'),
                'icon' => 'lucide-circle-arrow-out-up-right',
                'route_name' => 'super-admin.manage-h2h-users',
            ],
            [
                'title' => __('Manage Memberships'),
                'icon' => 'heroicon-o-ticket',
                'route_name' => 'super-admin.memberships',
            ],
            [
                'title' => __('Membership Inquiries'),
                'icon' => 'lucide-calendar-search',
                'route_name' => 'super-admin.membership-inquiries',
                'badge' => true,
                'new_membership_inquiry_count' => $membershipInquiryCount,
            ],
            [
                'title' => __('Manage Partners'),
                'icon' => 'lucide-link',
                'route_name' => 'super-admin.manage-partners',
            ],
            [
                'title' => __('Manage Subclients'),
                'icon' => 'lucide-send-to-back',
                'route_name' => 'manage-subclients',
            ],
            [
                'title' => __('Consumer Profiles'),
                'icon' => 'heroicon-o-user-group',
                'route_name' => 'manage-consumers',
            ],
            [
                'title' => __('Export / Reporting'),
                'icon' => 'heroicon-o-chart-bar',
                'items' => [
                    [
                        'title' => __('Generate a Report'),
                        'icon' => 'heroicon-o-arrow-down-tray',
                        'route_name' => 'generate-reports',
                        'badge' => false,
                        'count' => 0,
                    ],
                    [
                        'title' => __('Schedule a Report'),
                        'icon' => 'heroicon-o-clock',
                        'route_name' => 'schedule-export',
                    ],
                ],
            ],
            [
                'title' => __('Communications'),
                'icon' => 'lucide-messages-square',
                'items' => [
                    [
                        'title' => __('Templates'),
                        'icon' => 'lucide-file-terminal',
                        'route_name' => 'super-admin.communication.templates',
                    ],
                    [
                        'title' => __('Groups'),
                        'icon' => 'lucide-users-round',
                        'route_name' => 'super-admin.communication.groups',
                    ],
                    [
                        'title' => __('Schedule Campaign'),
                        'icon' => 'lucide-alarm-clock-check',
                        'route_name' => 'super-admin.communication.campaigns',
                    ],
                    [
                        'title' => __('Campaign Tracker'),
                        'icon' => 'lucide-route',
                        'route_name' => 'super-admin.communication.campaign-trackers',
                    ],
                ],
            ],
            [
                'title' => __('Automated Communication'),
                'icon' => 'heroicon-s-cog',
                'items' => [
                    [
                        'title' => __('Templates'),
                        'icon' => 'heroicon-o-book-open',
                        'route_name' => 'super-admin.automated-templates',
                        'count' => 0,
                    ],
                    [
                        'title' => __('Communication Statuses'),
                        'icon' => 'heroicon-o-key',
                        'route_name' => 'super-admin.configure-communication-status',
                    ],
                    [
                        'title' => __('Campaigns'),
                        'icon' => 'heroicon-o-calendar-days',
                        'route_name' => 'super-admin.automation-campaigns',
                    ],
                    [
                        'title' => __('Communication Histories'),
                        'icon' => 'heroicon-o-server-stack',
                        'route_name' => 'super-admin.automated-communication-histories',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function sidebarMenus(User $user, int $newOfferCount): array
    {
        $menus = [
            [
                'title' => __('Portal Help Library'),
                'icon' => 'lucide-hand-helping',
                'items' => [
                    [
                        'title' => __('Video Tutorial'),
                        'icon' => 'lucide-file-video-2',
                        'route_name' => 'creditor.video-tutorial',
                    ],
                ],
            ],
            [
                'title' => __('Dashboard'),
                'icon' => 'lucide-circle-gauge',
                'route_name' => 'creditor.dashboard',
            ],
            [
                'title' => __('Consumer Offers'),
                'icon' => 'heroicon-o-receipt-percent',
                'route_name' => 'creditor.consumer-offers',
                'badge' => true,
                'new_offer_count' => $newOfferCount,
            ],
            [
                'title' => __('Consumer Accounts Profiles'),
                'icon' => 'heroicon-o-user-group',
                'route_name' => 'manage-consumers',
            ],
            [
                'title' => __('File Import'),
                'icon' => 'lucide-import',
                'items' => [
                    [
                        'title' => __('Upload a File'),
                        'icon' => 'heroicon-m-cloud-arrow-up',
                        'route_name' => 'creditor.import-consumers.index',
                    ],
                    [
                        'title' => __('Upload History'),
                        'icon' => 'lucide-file-clock',
                        'route_name' => 'creditor.import-consumers.file-upload-history',
                    ],
                ],
            ],
            [
                'title' => __('Manage Account'),
                'icon' => 'heroicon-o-cog',
                'items' => $this->manageAccountMenus($user),
            ],
            [
                'title' => __('Communications'),
                'icon' => 'lucide-messages-square',
                'items' => [
                    [
                        'title' => __('EcoLetter Template'),
                        'icon' => 'lucide-file-terminal',
                        'route_name' => 'creditor.communication.e-letters',
                    ],
                    [
                        'title' => __('CFPB Template'),
                        'icon' => 'lucide-messages-square',
                        'route_name' => 'creditor.cfpb-communication',
                    ],
                    [
                        'title' => __('Groups'),
                        'icon' => 'lucide-users-round',
                        'route_name' => 'creditor.communication.groups',
                    ],
                    [
                        'title' => __('Schedule Campaign'),
                        'icon' => 'lucide-alarm-clock-check',
                        'route_name' => 'creditor.communication.campaigns',
                    ],
                    [
                        'title' => __('Campaign Tracker'),
                        'icon' => 'lucide-route',
                        'route_name' => 'creditor.communication.campaign-trackers',
                    ],
                ],
            ],
        ];

        if (app(SetupWizardService::class)->cachingRemainingRequireStepCount($user) > 0) {
            array_unshift($menus, [
                'title' => __('Set Up Wizard'),
                'icon' => 'lucide-monitor-cog',
                'route_name' => 'creditor.setup-wizard',
            ]);
        }

        return $menus;
    }

    private function manageAccountMenus(User $user): array
    {
        $manageAccountMenus = [
            [
                'title' => __('Merchant Account'),
                'icon' => 'heroicon-o-currency-dollar',
                'route_name' => 'creditor.merchant-settings',
            ],
            [
                'title' => __('Header Profile(s)'),
                'icon' => 'lucide-git-compare',
                'route_name' => 'creditor.import-consumers.upload-file',
            ],
            [
                'title' => __('SFTP Connection(s)'),
                'icon' => 'lucide-earth-lock',
                'route_name' => 'creditor.sftp',
            ],
            [
                'title' => __('Sub Account(s)'),
                'icon' => 'heroicon-s-user-group',
                'route_name' => 'manage-subclients',
            ],
            [
                'title' => __('Pay Terms Offers'),
                'icon' => 'lucide-receipt-text',
                'route_name' => 'creditor.pay-terms',
            ],
            [
                'title' => __('Custom Consumer Offers'),
                'icon' => 'lucide-badge-percent',
                'route_name' => 'creditor.consumer-pay-terms',
            ],
            [
                'title' => __('Terms & Conditions'),
                'icon' => 'lucide-badge-info',
                'route_name' => 'creditor.terms-conditions',
            ],
            [
                'title' => __('About Us & Contact'),
                'icon' => 'lucide-users',
                'route_name' => 'creditor.about-us.create-or-update',
            ],
            [
                'title' => __('Logo & Links'),
                'icon' => 'heroicon-o-link',
                'route_name' => 'creditor.personalized-logo-and-link',
            ],
        ];

        if ($user->parent_id === null) {
            array_push($manageAccountMenus, [
                'title' => __('Users'),
                'icon' => 'heroicon-o-user-plus',
                'route_name' => 'creditor.users',
            ]);
        }

        array_push($manageAccountMenus, [
            'title' => __('Reports'),
            'icon' => 'heroicon-o-chart-bar-square',
            'items' => [
                [
                    'title' => __('Generate a Report'),
                    'icon' => 'heroicon-o-arrow-down-tray',
                    'route_name' => 'generate-reports',
                ],
                [
                    'title' => __('Schedule a Report'),
                    'icon' => 'heroicon-o-clock',
                    'route_name' => 'creditor.schedule-export',
                ],
                [
                    'title' => __('Report History'),
                    'icon' => 'heroicon-s-document-duplicate',
                    'route_name' => 'reports.history',
                ],
            ],
        ]);

        return $manageAccountMenus;
    }
}
