<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CreditorCurrentStep;
use App\Enums\MembershipTransactionStatus;
use App\Enums\Role as EnumRole;
use App\Livewire\Creditor\BillingHistoryPage;
use App\Models\Membership;
use App\Models\MembershipTransaction;
use App\Models\YnTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class BillingHistoryPageTest extends AuthTestCase
{
    #[Test]
    public function it_can_render_the_livewire_page_of_billing_history(): void
    {
        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);
        $this->company->update(['current_step' => CreditorCurrentStep::COMPLETED]);

        $this->get(route('creditor.billing-history'))
            ->assertSeeLivewire(BillingHistoryPage::class)
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_view_of_livewire_page(): void
    {
        Livewire::test(BillingHistoryPage::class)
            ->assertViewIs('livewire.creditor.billing-history-page')
            ->assertOk();
    }

    #[Test]
    public function it_can_render_the_membership_transactions_for_billing_histories(): void
    {
        $membership = Membership::factory()->create();

        $membershipTransaction = MembershipTransaction::query()->create([
            'status' => MembershipTransactionStatus::SUCCESS,
            'company_id' => $this->user->company_id,
            'membership_id' => $membership->id,
            'price' => fake()->randomNumber(3, true),
            'tilled_transaction_id' => fake()->uuid(),
            'response' => [],
        ]);

        $ynTransaction = YnTransaction::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        Livewire::test(BillingHistoryPage::class)
            ->assertViewHas(
                'billingHistories',
                fn (LengthAwarePaginator $billingHistories) => $billingHistories->getCollection()->contains($membershipTransaction)
                && $billingHistories->getCollection()->contains($ynTransaction->id)
            )
            ->assertOk();
    }

    #[Test]
    public function it_can_download_agreement_of_creditor_membership_transaction(): void
    {
        $membershipTransaction = MembershipTransaction::factory()->create();

        Pdf::shouldReceive('setOption')->once()->andReturnSelf();
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.creditor.membership-transaction-invoice', Mockery::on(fn ($data): bool => $data['membershipTransaction']->id === $membershipTransaction->id))
            ->andReturnSelf();
        Pdf::shouldReceive('output')->once()->andReturn('pdf-content');

        Livewire::actingAs($this->user)
            ->test(BillingHistoryPage::class)
            ->call('downloadInvoice', $membershipTransaction->id, 'membership')
            ->assertFileDownloaded('membership_' . $membershipTransaction->id . '_you_negotiate_invoice.pdf');
    }

    #[Test]
    public function it_can_download_agreement_of_creditor_yn_transactions(): void
    {
        $ynTransaction = YnTransaction::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        Pdf::shouldReceive('setOption')->once()->andReturnSelf();
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pdf.creditor.yn-transaction-invoice', Mockery::on(fn ($data): bool => $data['ynTransaction']->id === $ynTransaction->id))
            ->andReturnSelf();
        Pdf::shouldReceive('output')->once()->andReturn('pdf-content');

        Livewire::actingAs($this->user)
            ->test(BillingHistoryPage::class)
            ->call('downloadInvoice', $ynTransaction->id, 'yn')
            ->assertFileDownloaded('yn_' . $ynTransaction->id . '_you_negotiate_invoice.pdf');
    }

    #[Test]
    public function it_can_export_billing_transaction_report_data(): void
    {
        Storage::fake();

        YnTransaction::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => MembershipTransactionStatus::SUCCESS,
        ]);

        Livewire::actingAs($this->user)
            ->test(BillingHistoryPage::class)
            ->call('export')
            ->assertFileDownloaded()
            ->assertOk();
    }
}
