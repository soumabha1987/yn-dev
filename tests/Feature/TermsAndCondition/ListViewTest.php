<?php

declare(strict_types=1);

namespace Tests\Feature\TermsAndCondition;

use App\Enums\CustomContentType;
use App\Livewire\Creditor\TermsAndConditions\ListView;
use App\Models\CustomContent;
use App\Models\Subclient;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\AuthTestCase;

class ListViewTest extends AuthTestCase
{
    #[Test]
    #[DataProvider('paginationRequest')]
    public function terms_and_condition_list_page_with_pagination(int $page, array $seeData, array $dontSeeData): void
    {
        CustomContent::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        Subclient::factory(15)
            ->sequence(fn ($sequence) => ['subclient_name' => 'sub_client_' . $sequence->index])
            ->create([
                'company_id' => $this->user->company_id,
            ])
            ->each(function ($subclient) {
                CustomContent::factory()
                    ->for($subclient)
                    ->create([
                        'company_id' => $this->user->company_id,
                        'type' => CustomContentType::TERMS_AND_CONDITIONS,
                    ]);
            });

        $this->assertDatabaseCount(CustomContent::class, 16);

        Livewire::withQueryParams(['page' => $page])
            ->test(ListView::class)
            ->set('perPage', 10)
            ->call('setPage', $page)
            ->assertSessionHas('tables.' . Auth::id() . '_' . Str::replace('\\', '_', ListView::class) . '_per_page', 10)
            ->assertSee($seeData)
            ->assertDontSee($dontSeeData)
            ->assertSuccessful();
    }

    #[Test]
    public function delete_method_for_default_terms_and_conditions(): void
    {
        $customContent = CustomContent::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'subclient_id' => null,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        Livewire::test(ListView::class)
            ->call('delete', $customContent->id)
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('YN requires Master Terms and Conditions template on all member accounts. You can edit, however not delete.'));

        $this->assertDatabaseHas(CustomContent::class, ['id' => $customContent->id]);
    }

    #[Test]
    public function delete_method_for_non_default_terms_and_conditions(): void
    {
        $customContent = CustomContent::factory()
            ->create([
                'company_id' => $this->user->company_id,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        $this->assertDatabaseHas(CustomContent::class, ['id' => $customContent->id]);

        Livewire::test(ListView::class)
            ->call('delete', $customContent->id)
            ->assertDispatched('close-confirmation-box');

        Notification::assertNotified(__('Terms and Conditions template deleted.'));

        $this->assertDatabaseMissing(CustomContent::class, ['id' => $customContent->id]);
    }

    #[Test]
    public function it_can_render_the_view_page_with_deleted_subclient_data(): void
    {
        $customContent = CustomContent::factory()
            ->for($this->user->company)
            ->create([
                'subclient_id' => null,
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        $deleteSubclientCustomContent = CustomContent::factory()
            ->for($this->user->company)
            ->for(Subclient::factory()->create([
                'company_id' => $this->user->company_id,
                'deleted_at' => now(),
            ]))
            ->create(['type' => CustomContentType::TERMS_AND_CONDITIONS]);

        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.terms-and-conditions.list-view')
            ->assertViewHas(
                'termsAndConditions',
                fn (LengthAwarePaginator $termsAndConditionContents) => $customContent->is($termsAndConditionContents->getCollection()->first())
                    && $termsAndConditionContents->getCollection()->doesntContain($deleteSubclientCustomContent)
            )
            ->assertSee(__('Master T&C Required'))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_created_at(string $direction): void
    {
        $createdCustomContents = CustomContent::factory(10)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => ['created_at' => now()->subDays($sequence->index + 2)])
            ->create([
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        Livewire::withQueryParams(['sort' => 'created_at', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'created_at')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'termsAndConditions',
                fn (LengthAwarePaginator $termsAndConditions) => $direction === 'ASC'
                    ? $createdCustomContents->last()->is($termsAndConditions->getCollection()->first())
                    : $createdCustomContents->first()->is($termsAndConditions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_subclient_name(string $direction): void
    {
        $createdCustomContents = CustomContent::factory(10)
            ->for($this->user->company)
            ->sequence(fn (Sequence $sequence) => [
                'subclient_id' => Subclient::factory()->state([
                    'company_id' => $this->user->company_id,
                    'subclient_name' => range('A', 'Z')[$sequence->index + 2],
                ]),
            ])
            ->create([
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        Livewire::withQueryParams(['sort' => 'name', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'termsAndConditions',
                fn (LengthAwarePaginator $termsAndConditions) => $direction === 'ASC'
                    ? $createdCustomContents->first()->is($termsAndConditions->getCollection()->first())
                    : $createdCustomContents->last()->is($termsAndConditions->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_type(string $direction): void
    {
        $createdCustomContents = CustomContent::factory()
            ->for($this->user->company)
            ->forEachSequence(
                ['subclient_id' => null],
                ['subclient_id' => Subclient::factory()->state(['company_id' => $this->user->company_id])]
            )
            ->create([
                'type' => CustomContentType::TERMS_AND_CONDITIONS,
            ]);

        Livewire::withQueryParams(['sort' => 'type', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'termsAndConditions',
                fn (LengthAwarePaginator $termsAndConditions) => $direction === 'ASC'
                    ? $createdCustomContents->first()->is($termsAndConditions->getCollection()->first())
                    : $createdCustomContents->last()->is($termsAndConditions->getCollection()->first())
            );
    }

    public static function paginationRequest(): array
    {
        return [
            [
                'page' => 1,
                ['Sub_Client_0', 'Sub_Client_4', 'Sub_Client_8'],
                ['Sub_Client_9', 'Sub_Client_11', 'Sub_Client_13'],
            ],
            [
                'page' => 2,
                ['Sub_Client_9', 'Sub_Client_11', 'Sub_Client_13'],
                ['Sub_Client_0', 'Sub_Client_4', 'Sub_Client_8'],
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
