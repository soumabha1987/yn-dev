<?php

declare(strict_types=1);

namespace Tests\Feature\Consumer\Profile;

use AllowDynamicProperties;
use App\Livewire\Consumer\Profile\PersonalizeLogo;
use App\Models\Consumer;
use App\Models\ConsumerPersonalizedLogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[AllowDynamicProperties]
class PersonalizeLogoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = Consumer::factory()->create();

        $this->withoutVite()
            ->actingAs($this->consumer, 'consumer');
    }

    #[Test]
    public function it_can_render_livewire_component_when_visit_the_route(): void
    {
        $this->get(route('consumer.personalize_logo'))
            ->assertOk()
            ->assertSeeLivewire(PersonalizeLogo::class);
    }

    #[Test]
    public function it_can_render_livewire_component_correct_view(): void
    {
        Livewire::test(PersonalizeLogo::class)
            ->assertViewIs('livewire.consumer.profile.personalize-logo')
            ->assertOk();
    }

    #[Test]
    public function it_can_reset_form(): void
    {
        Livewire::test(PersonalizeLogo::class)
            ->assertViewIs('livewire.consumer.profile.personalize-logo')
            ->call('resetForm')
            ->assertSet('form.primary_color', '#2563eb')
            ->assertSet('form.secondary_color', '#000000')
            ->assertOk();
    }

    #[Test]
    public function it_can_allow_only_hex_colors(): void
    {
        Livewire::test(PersonalizeLogo::class)
            ->set('form.primary_color', fake()->colorName())
            ->set('form.secondary_color', fake()->colorName())
            ->call('createOrUpdate')
            ->assertHasErrors([
                'form.primary_color' => ['hex_color'],
                'form.secondary_color' => ['hex_color'],
            ])
            ->assertOk();
    }

    #[Test]
    public function it_can_update_consumer_personalized_logo(): void
    {
        $consumerPersonalizedLogo = ConsumerPersonalizedLogo::factory()->create(['consumer_id' => $this->consumer->id]);

        Livewire::test(PersonalizeLogo::class)
            ->set('form.primary_color', $primaryColor = fake()->hexColor())
            ->set('form.secondary_color', $secondaryColor = fake()->hexColor())
            ->call('createOrUpdate')
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertEquals($primaryColor, $consumerPersonalizedLogo->refresh()->primary_color);
        $this->assertEquals($secondaryColor, $consumerPersonalizedLogo->secondary_color);
    }

    #[Test]
    public function it_can_create_consumer_personalized_logo(): void
    {
        $file = UploadedFile::fake()->create('test.jpg');

        Cache::put('personalized-logo', 'hello');

        Livewire::test(PersonalizeLogo::class)
            ->set('form.primary_color', $primaryColor = fake()->hexColor())
            ->set('form.secondary_color', $secondaryColor = fake()->hexColor())
            ->set('form.image', $file)
            ->call('createOrUpdate')
            ->assertHasNoErrors()
            ->assertDispatched('set-header-logo')
            ->assertOk();

        $this->assertNull(Cache::get("personalized-logo-{$this->consumer->id}"));
        $this->assertDatabaseHas(ConsumerPersonalizedLogo::class, [
            'consumer_id' => $this->consumer->id,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
        ]);
        $this->assertNotNull($this->consumer->consumerProfile->refresh()->image);
    }

    #[Test]
    public function it_only_allows_the_image_mime_type(): void
    {
        $file = UploadedFile::fake()->create('test.mov');

        Livewire::test(PersonalizeLogo::class)
            ->set('form.image', $file)
            ->call('createOrUpdate')
            ->assertHasErrors(['form.image' => ['mimes']])
            ->assertHasNoErrors('form.primary_color', 'form.secondary_color')
            ->assertOk();
    }
}
