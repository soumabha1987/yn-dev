<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsumerStatus;
use App\Enums\Role as EnumsRole;
use App\Livewire\Creditor\GlobalSearch;
use App\Models\Consumer;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class GlobalSearchTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => EnumsRole::CREDITOR]);

        $this->user->assignRole($role);
    }

    #[Test]
    public function it_can_search_by_consumer(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane', 'last_name' => 'Doe'],
                ['first_name' => 'John', 'last_name' => 'Smith'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = 'Doe')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_search_by_account_number(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['account_number' => '123456'],
                ['account_number' => '654321'],
                ['account_number' => '123654'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = '654321')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_search_by_member_account_number(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['member_account_number' => '123456'],
                ['member_account_number' => '654321'],
                ['member_account_number' => '123654'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = '654321')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_search_by_mobile_number(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['mobile1' => '123456'],
                ['mobile1' => '654321'],
                ['mobile1' => '123654'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = '654321')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_search_by_consumer_email(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['email1' => 'test@test.com'],
                ['email1' => 'test1@test.com'],
                ['email1' => 'test2@test.com'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = 'test1@test.com')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_search_by_last4ssn(): void
    {
        Consumer::factory()
            ->forEachSequence(
                ['last4ssn' => '1234'],
                ['last4ssn' => '4321'],
                ['last4ssn' => '6543'],
            )
            ->for($this->company)
            ->create(['status' => ConsumerStatus::UPLOADED]);

        Livewire::test(GlobalSearch::class)
            ->set('search', $search = '4321')
            ->call('searchConsumer')
            ->assertSessionHas('search', $search)
            ->assertRedirect(route('manage-consumers', ['search' => $search]));
    }

    #[Test]
    public function it_can_reset_search(): void
    {
        Livewire::test(GlobalSearch::class)
            ->set('search', 'Doe')
            ->call('resetSearch')
            ->assertOk()
            ->assertSet('search', '')
            ->assertSessionHas('search', '')
            ->assertDispatched('refresh-global-search');
    }
}
