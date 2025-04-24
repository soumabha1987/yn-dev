<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsumerStatus;
use App\Models\Consumer;
use App\Models\PersonalizedLogo;
use App\Services\EncryptDecryptService;
use Faker\Extension\PersonExtension;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationLinkControllerTest extends TestCase
{
    #[Test]
    public function it_can_redirect_if_consumer_is_not_found(): void
    {
        config([
            'services.yng.key' => 'test',
            'services.yng.short_link' => 'https://yneg.link/',
        ]);

        Consumer::factory()->create([
            'invitation_link' => 'https://yneg.link/faker',
            'status' => ConsumerStatus::UPLOADED,
        ]);

        $response = $this->postJson(route('find-invitation-link'), [
            'key' => bin2hex('test'),
            'tag' => bin2hex('test'),
        ]);

        $response->assertRedirect('https://consumer.younegotiate.com/login')
            ->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function it_can_response_json_redirect_url(): void
    {
        $consumer = Consumer::factory()->create([
            'invitation_link' => 'https://yneg.link/test',
            'status' => ConsumerStatus::UPLOADED->value,
        ]);

        PersonalizedLogo::query()->create([
            'company_id' => $consumer->company_id,
            'customer_communication_link' => fake()->name(PersonExtension::GENDER_MALE),
        ]);

        config([
            'services.yng.key' => 'test',
            'services.yng.short_link' => 'https://yneg.link/',
        ]);

        $response = $this->postJson(route('find-invitation-link'), [
            'tag' => app(EncryptDecryptService::class)->encrypt('test', config('services.yng.key')),
        ]);

        $response->assertRedirect('https://consumer.younegotiate.com/login')
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertEquals($consumer->refresh()->status, ConsumerStatus::UPLOADED);
    }

    #[Test]
    public function it_can_response_json_redirect_to_login(): void
    {
        $consumer = Consumer::factory()->create([
            'invitation_link' => 'https://yneg.link/test',
            'status' => ConsumerStatus::UPLOADED->value,
        ]);

        PersonalizedLogo::query()->create([
            'company_id' => $consumer->company_id,
            'customer_communication_link' => fake()->name(PersonExtension::GENDER_MALE),
        ]);

        config([
            'services.yng.key' => 'test',
            'services.yng.short_link' => 'https://yneg.link/',
        ]);

        $response = $this->postJson(route('find-invitation-link'), [
            'tag' => app(EncryptDecryptService::class)->encrypt('test1111', config('services.yng.key')),
        ]);

        $response->assertRedirect('https://consumer.younegotiate.com/login');
    }
}
