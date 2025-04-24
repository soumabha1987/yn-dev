<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Partner;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PartnerMonthlyReportsExport implements FromView
{
    public function __construct(
        private Partner $partner,
        private float $totalAmount,
        private float $partnerTotalAmount,
    ) {}

    public function view(): View
    {
        return view('exports.partner_report', [
            'partner' => $this->partner,
            'totalAmount' => $this->totalAmount,
            'partnerTotalAmount' => $this->partnerTotalAmount,
        ]);
    }
}
