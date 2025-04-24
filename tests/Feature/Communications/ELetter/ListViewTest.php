<?php

declare(strict_types=1);

namespace Tests\Feature\Communications\ELetter;

use App\Enums\Role as EnumRole;
use App\Enums\TemplateType;
use App\Livewire\Creditor\Communications\ELetter\ListView;
use App\Models\Company;
use App\Models\Template;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\AuthTestCase;

class ListViewTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumRole::CREDITOR]);

        $this->user->assignRole($role);
    }

    #[Test]
    public function it_renders_the_component_with_view_page(): void
    {
        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.e-letter.list-view')
            ->assertViewHas('eLetters', fn (LengthAwarePaginator $eLetters) => $eLetters->isEmpty())
            ->assertSee(__('No result found'))
            ->assertOk();
    }

    #[Test]
    public function it_renders_the_component_with_e_letters_data(): void
    {
        $templates = Template::factory()
            ->forEachSequence(
                ['company_id' => $this->user->company_id],
                ['company_id' => Company::factory()->state([])]
            )
            ->create(['type' => TemplateType::E_LETTER]);

        Livewire::test(ListView::class)
            ->assertViewIs('livewire.creditor.communications.e-letter.list-view')
            ->assertViewHas('eLetters', function (LengthAwarePaginator $eLetters) use ($templates) {
                return $eLetters->getCollection()->contains($templates->first())
                    && $eLetters->getCollection()->doesntContain($templates->last());
            })
            ->assertOk();
    }

    #[Test]
    public function it_can_deletes_a_template(): void
    {
        $template = Template::factory()->create();

        Livewire::test(ListView::class)
            ->call('delete', $template->id)
            ->assertDispatched('close-confirmation-box')
            ->assertDispatched('reset-parent')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotified(__('eLetter deleted.'));

        $this->assertSoftDeleted('templates', ['id' => $template->id]);
    }

    #[Test]
    public function it_can_deletes_a_template_by_super_admin(): void
    {
        $superAdmin = User::factory()->create();

        $role = Role::query()->create(['name' => EnumRole::SUPERADMIN]);

        $superAdmin->assignRole($role);

        $this->actingAs($superAdmin);

        $template = Template::factory()
            ->for($superAdmin)
            ->for($superAdmin->company)
            ->create();

        $type = $template->type->value;

        Livewire::test(ListView::class)
            ->call('delete', $template->id)
            ->assertDispatched('close-confirmation-box')
            ->assertDispatched('reset-parent')
            ->assertHasNoErrors()
            ->assertOk();

        Notification::assertNotified(__(':type deleted successfully!', ['type' => ucfirst($type)]));

        $this->assertSoftDeleted('templates', ['id' => $template->id]);
    }

    #[Test]
    public function it_can_search_result(): void
    {
        $templates = Template::factory(2)
            ->sequence(fn (Sequence $sequence) => ['name' => 'Test Template_' . ($sequence->index + 2)])
            ->create([
                'company_id' => $this->user->company_id,
                'type' => TemplateType::E_LETTER,
            ]);

        Livewire::withQueryParams(['search' => 'Test Template_2'])
            ->test(ListView::class)
            ->assertViewHas('eLetters', function (LengthAwarePaginator $eLetters) use ($templates) {
                return $eLetters->getCollection()->contains($templates->first())
                    && $eLetters->getCollection()->doesntContain($templates->last());
            })
            ->assertSee('Test Template_2')
            ->assertDontSee('Test Template_3')
            ->assertOk();
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_name(string $direction): void
    {
        $templates = Template::factory(4)
            ->sequence(
                fn (Sequence $sequence) => [
                    'name' => 'test' . ($sequence->index),
                ]
            )
            ->create([
                'company_id' => $this->user->company_id,
                'type' => TemplateType::E_LETTER,
            ]);

        Livewire::withQueryParams(['sort' => 'template-name', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'template-name')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'eLetters',
                fn (LengthAwarePaginator $eLetters) => $direction === 'ASC'
                    ? $templates->first()->is($eLetters->getCollection()->first())
                    : $templates->last()->is($eLetters->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_created_at(string $direction): void
    {
        $templates = Template::factory(4)
            ->sequence(fn (Sequence $sequence): array => ['created_at' => now()->addDays($sequence->index + 5)])
            ->create([
                'company_id' => $this->user->company_id,
                'type' => TemplateType::E_LETTER,
            ]);

        Livewire::withQueryParams(['sort' => 'created-on', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'created-on')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'eLetters',
                fn (LengthAwarePaginator $eLetters) => $direction === 'ASC'
                    ? $templates->first()->is($eLetters->getCollection()->first())
                    : $templates->last()->is($eLetters->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_type(string $direction): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $templates = Template::factory()
            ->forEachSequence(
                ['type' => TemplateType::EMAIL],
                ['type' => TemplateType::SMS],
            )
            ->create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);

        Livewire::actingAs($user)
            ->withQueryParams(['sort' => 'type', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'type')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'eLetters',
                fn (LengthAwarePaginator $eLetters) => $direction === 'ASC'
                    ? $templates->first()->is($eLetters->getCollection()->first())
                    : $templates->last()->is($eLetters->getCollection()->first())
            );
    }

    #[Test]
    #[DataProvider('sortDirection')]
    public function it_can_sort_by_created_by(string $direction): void
    {
        $user = User::factory()->create();

        $user->assignRole(Role::query()->create(['name' => EnumRole::SUPERADMIN]));

        $templates = Template::factory(4)
            ->sequence(fn (Sequence $sequence) => ['user_id' => User::factory()->state(['name' => range('A', 'Z')[$sequence->index + 2]])])
            ->create([
                'company_id' => $user->company_id,
                'type' => fake()->randomElement([TemplateType::EMAIL, TemplateType::SMS]),
            ]);

        Livewire::actingAs($user)
            ->withQueryParams(['sort' => 'created-by', 'direction' => $direction === 'ASC'])
            ->test(ListView::class)
            ->assertOk()
            ->assertSet('sortCol', 'created-by')
            ->assertSet('sortAsc', $direction === 'ASC')
            ->assertViewHas(
                'eLetters',
                fn (LengthAwarePaginator $eLetters) => $direction === 'ASC'
                    ? $templates->first()->is($eLetters->getCollection()->first())
                    : $templates->last()->is($eLetters->getCollection()->first())
            );
    }

    public static function sortDirection(): array
    {
        return [
            ['ASC'],
            ['DESC'],
        ];
    }
}
