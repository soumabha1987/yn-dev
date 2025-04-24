<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\ManageCreditors;

use App\Enums\MerchantType;
use App\Models\Company;
use App\Services\ScheduleTransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Livewire\Component;

class ViewPage extends Component
{
    public Company $company;

    public string $merchantName = '';

    public bool $ccMerchant = false;

    public bool $achMerchant = false;

    public function mount(): void
    {
        abort_if(! $this->company->creditorUser, Response::HTTP_NOT_FOUND);

        $this->company->loadMissing(
            'merchants:id,company_id,merchant_name,merchant_type',
            'activeCompanyMembership.membership:id,name,fee,price,frequency,upload_accounts_limit',
            'partner:id,name,contact_email,contact_phone,revenue_share',
            'users:id,company_id,name,email,phone_no,image',
            'creditorUser:id,company_id,name,email,phone_no,image'
        );

        $this->merchantName = $this->company->merchants->first()?->merchant_name->value ?? '';

        $this->ccMerchant = $this->company->merchants->where('merchant_type', MerchantType::CC)->isNotEmpty();
        $this->achMerchant = $this->company->merchants->where('merchant_type', MerchantType::ACH)->isNotEmpty();
    }

    public function render(): View
    {
        return view('livewire.creditor.manage-creditors.view-page')
            ->with([
                'consumerCount' => $this->company->consumers->count(),
                'scheduleTransactionAmount' => app(ScheduleTransactionService::class)->calculateScheduledAndFailedTransactionAmount($this->company),
            ])
            ->title(__('Creditor Details'));
    }
}
