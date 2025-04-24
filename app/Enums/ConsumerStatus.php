<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\Names;
use App\Enums\Traits\Values;
use App\Models\Consumer;

enum ConsumerStatus: string
{
    use Names;
    use Values;

    case JOINED = 'joined';
    case DEACTIVATED = 'deactivated';
    case PAYMENT_ACCEPTED = 'payment_accepted';
    case PAYMENT_SETUP = 'payment_setup';
    case RENEGOTIATE = 'renegotiate';
    case PAYMENT_DECLINED = 'payment_declined';
    case NOT_PAYING = 'not_paying';
    case SETTLED = 'settled';
    case DISPUTE = 'dispute';
    case UPLOADED = 'uploaded';
    case VISITED = 'visited';
    case NOT_VERIFIED = 'not_verified';
    case HOLD = 'hold';

    const STATE_STATUS_MAPPING = [
        'joined' => 'ready_to_negotiate',
        'payment_accepted' => null,
        'payment_setup' => 'active_negotiation',
        'notice_sent' => 'my_notice_responses',
        'payment_declined' => 'payment_declined',
        'settled' => 'settled',
        'dispute' => 'disputed',
        'not_paying' => 'not_paying',
        'deactivated' => 'deactivated',
        'hold' => 'hold',
    ];

    /**
     * @return array<string, string>
     */
    public static function displayFilterBox(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case): array => [$case->displayName() => $case->value])
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    public static function sortingPriorityByStates(): array
    {
        return [
            'approved_but_payment_pending' => 1,
            'ready_to_negotiate' => 2,
            'active_negotiation' => 3,
            'my_notice_responses' => 4,
            'payment_plans' => 5,
            'payment_declined' => 6,
            'settled' => 7,
            'hold' => 8,
            'disputed' => 9,
            'not_paying' => 10,
            'deactivated' => 11,
        ];
    }

    /**
     * @return array<string>
     */
    public static function notVerified(): array
    {
        return [
            self::UPLOADED->value,
            self::VISITED->value,
            self::NOT_VERIFIED->value,
        ];
    }

    public function displayLabel(): string
    {
        return match ($this) {
            self::JOINED => 'Ready to Negotiate',
            self::PAYMENT_ACCEPTED => 'Payment Plans',
            self::PAYMENT_SETUP => 'Active Negotiations',
            self::PAYMENT_DECLINED => 'Renegotiate',
            self::DISPUTE => 'Disputed',
            self::DEACTIVATED => 'Creditor Removed',
            self::SETTLED => 'Paid Accounts',
            self::HOLD => 'On Hold',
            default => $this->displayName(),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function consumerStates(): array
    {
        return [
            'all' => [
                'tab' => 'All',
                'filters' => [],
            ],
            'ready_to_negotiate' => [
                'tab' => 'New Offers!',
                'card_title' => 'New Offer!',
                'class' => 'bg-secondary/10 text-secondary hover:bg-secondary/20 focus:bg-secondary/20',
                'active_class' => 'outline outline-2 outline-secondary bg-secondary/20',
                'card_tag_class' => 'bg-secondary text-white hover:bg-secondary-focus',
                'grid_tag_class' => 'bg-secondary/10 text-secondary hover:bg-secondary/20 focus:bg-secondary/20',
                'filters' => [
                    'status' => self::JOINED,
                ],
            ],
            'approved_but_payment_pending' => [
                'tab' => 'Final Step to Close Deal',
                'card_title' => 'Add Payment Method',
                'class' => 'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20',
                'active_class' => 'outline outline-2 outline-success bg-success/20',
                'card_tag_class' => 'bg-success text-white hover:bg-success-focus',
                'grid_tag_class' => 'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20',
                'filters' => [
                    'status' => self::PAYMENT_ACCEPTED,
                    'payment_setup' => 0,
                ],
            ],
            'active_negotiation' => [
                'tab' => 'Active Negotiations',
                'class' => 'bg-cyan-300/20 text-cyan-500 hover:bg-cyan-500/20 focus:bg-cyan-500/20',
                'active_class' => 'outline outline-2 outline-cyan-300 bg-cyan-300/20',
                'card_tag_class' => 'bg-cyan-500 text-white hover:bg-cyan-500',
                'grid_tag_class' => 'bg-cyan-500/10 text-cyan-500 hover:bg-cyan-500/20 focus:bg-cyan-500/20',
                'filters' => [
                    'status' => self::PAYMENT_SETUP,
                ],
            ],
            'my_notice_responses' => [
                'tab' => 'My Notice Responses',
                'card_title' => 'Pending Creditor Response',
                'class' => 'bg-pink-800/10 text-pink-800 hover:bg-pink-800/20 focus:bg-pink-800/20',
                'active_class' => 'outline outline-2 outline-pink-800 bg-pink-800/20',
                'card_tag_class' => 'bg-pink-800 text-white hover:bg-pink-900',
                'grid_tag_class' => 'bg-pink-200 text-pink-800 hover:bg-pink-300 focus:bg-pink-300',
                'filters' => [
                    'status' => 'notice_sent',
                ],
            ],
            'payment_plans' => [
                'tab' => 'Payment Plans',
                'card_title' => 'Payment Plan in Place',
                'class' => 'bg-info/10 text-info hover:bg-info/20 focus:bg-info/20',
                'active_class' => 'outline outline-2 outline-info bg-info/20',
                'card_tag_class' => 'bg-info text-white hover:bg-info-focus',
                'grid_tag_class' => 'bg-info/10 text-info hover:bg-info/20 focus:bg-info/20',
                'filters' => [
                    'status' => self::PAYMENT_ACCEPTED,
                    'payment_setup' => 1,
                ],
            ],
            'payment_declined' => [
                'tab' => 'Declined/Closed Negotiations',
                'card_title' => 'Declined/Closed Negotiation',
                'class' => 'bg-slate-200 text-slate-900 hover:bg-slate-300 focus:bg-slate-300',
                'active_class' => 'outline outline-2 outline-slate-900 bg-slate-300',
                'card_tag_class' => 'bg-slate-200 text-black hover:bg-slate-300',
                'grid_tag_class' => 'bg-slate-200 text-black hover:bg-slate-300 focus:bg-slate-300',
                'filters' => [
                    'status' => self::PAYMENT_DECLINED,
                ],
            ],
            'settled' => [
                'tab' => 'Paid Accounts',
                'card_title' => 'Paid Off!',
                'class' => 'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20',
                'active_class' => 'outline outline-2 outline-success bg-success/20',
                'card_tag_class' => 'bg-success text-white hover:bg-success-focus',
                'grid_tag_class' => 'bg-success/10 text-success hover:bg-success/20 focus:bg-success/20',
                'filters' => [
                    'status' => self::SETTLED,
                ],
            ],
            'disputed' => [
                'tab' => 'Disputed',
                'card_title' => 'Disputed',
                'class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'active_class' => 'outline outline-2 outline-error bg-error/20',
                'card_tag_class' => 'bg-error text-white hover:bg-error-focus',
                'grid_tag_class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'filters' => [
                    'status' => self::DISPUTE,
                ],
            ],
            'not_paying' => [
                'tab' => 'Not Paying',
                'card_title' => 'Reported Not Paying',
                'class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'active_class' => 'outline outline-2 outline-error bg-error/20',
                'card_tag_class' => 'bg-error text-white hover:bg-error-focus',
                'grid_tag_class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'filters' => [
                    'status' => self::NOT_PAYING,
                ],
            ],
            'deactivated' => [
                'tab' => 'Creditor Removed',
                'card_title' => 'Removed by Creditor',
                'class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'active_class' => 'outline outline-2 outline-error bg-error/20',
                'card_tag_class' => 'bg-error text-white hover:bg-error-focus',
                'grid_tag_class' => 'bg-error/10 text-error hover:bg-error/20 focus:bg-error/20',
                'filters' => [
                    'status' => self::DEACTIVATED,
                ],
            ],
            'hold' => [
                'tab' => 'On Hold',
                'card_title' => 'On Hold',
                'class' => 'bg-warning/10 text-warning hover:bg-warning/20 focus:bg-warning/20',
                'active_class' => 'outline outline-2 outline-warning bg-warning/20',
                'card_tag_class' => 'bg-warning text-white hover:bg-warning-focus',
                'grid_tag_class' => 'bg-warning/10 text-warning hover:bg-warning/20 focus:bg-warning/20',
                'filters' => [
                    'status' => self::HOLD,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function findStateOfAccount(Consumer $consumer): array
    {
        $states = collect(self::consumerStates());

        $stateKey = self::STATE_STATUS_MAPPING[$consumer->status->value] ?? null;

        return match (true) {
            $stateKey !== null => [
                $stateKey => $states->get($stateKey),
            ],
            $consumer->status === self::PAYMENT_ACCEPTED && $consumer->payment_setup => [
                'payment_plans' => $states->get('payment_plans'),
            ],
            default => [
                'approved_but_payment_pending' => $states->get('approved_but_payment_pending'),
            ],
        };
    }
}
