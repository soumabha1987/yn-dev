<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Jobs\DeleteTilledCustomerJob;
use App\Models\Company;
use App\Models\MembershipPaymentProfile;
use App\Services\TilledPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TilledPaymentServiceTest extends TestCase
{
    protected $tilledPaymentService;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tilledPaymentService = new TilledPaymentService;
        $this->company = Company::factory()->create();
        config(['services.merchant.tilled_merchant_account_id' => fake()->uuid()]);
    }

    #[Test]
    public function it_can_test_create_customer_with_payment_profile(): void
    {
        $data = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'tilled_response' => ['id' => 'payment method id'],
        ];

        Http::fake([
            'customers' => Http::response(['id' => $customerId = 'customer id']),
            'payment-methods/*/attach' => Http::response(['id' => 'attach customer id']),
        ]);

        $response = $this->tilledPaymentService->createOrUpdateCustomer($this->company, $data);

        $this->assertEquals($customerId, $response);
    }

    #[Test]
    public function it_can_test_update_customer_with_new_payment_profile_attach_detach_successful(): void
    {
        MembershipPaymentProfile::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'tilled_response' => ['id' => 'payment method id'],
        ];

        Http::fake([
            'customers/*' => Http::response(['id' => $customerId = 'customer id']),
            'payment-methods/*/attach' => Http::response(['id' => 'attach customer id']),
            'payment-methods/*/detach' => Http::response(['id' => 'detach customer id']),
        ]);

        $response = $this->tilledPaymentService->createOrUpdateCustomer($this->company, $data);

        $this->assertEquals($customerId, $response);
    }

    #[Test]
    public function it_can_test_update_customer_with_failed_detach_customer(): void
    {
        Queue::fake();

        MembershipPaymentProfile::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'tilled_response' => ['id' => 'payment method id'],
        ];

        Http::fake([
            'customers' => Http::response(['id' => 'customer id']),
            'payment-methods/*/attach' => Http::response(['id' => 'attach customer id']),
            'payment-methods/*/detach' => Http::response(false),
        ]);

        $this->tilledPaymentService->createOrUpdateCustomer($this->company, $data);

        Queue::assertPushed(DeleteTilledCustomerJob::class);
    }

    #[Test]
    public function it_can_test_update_customer_with_successful_detach_customer_and_failed_attach_customer(): void
    {
        MembershipPaymentProfile::factory()
            ->create([
                'company_id' => $this->company->id,
                'tilled_payment_method_id' => fake()->uuid(),
            ]);

        $data = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'tilled_response' => ['id' => 'payment method id'],
        ];

        Http::fake([
            'customers/*' => Http::response(['id' => $customerId = 'customer id']),
            'payment-methods/*/attach' => Http::response(false),
            'payment-methods/*/detach' => Http::response(['id' => 'detach customer id']),
        ]);

        $response = $this->tilledPaymentService->createOrUpdateCustomer($this->company, $data);

        $this->assertNull($response);
    }

    #[Test]
    public function it_can_test_update_customer_with_successful_detach_customer_and_failed_attach_customer_then_after_send_new_payment_method(): void
    {
        MembershipPaymentProfile::factory()
            ->create([
                'company_id' => $this->company->id,
                'tilled_payment_method_id' => '',
            ]);

        $data = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'tilled_response' => ['id' => 'payment method id'],
        ];

        Http::fake([
            'customers/*' => Http::response(['id' => $customerId = 'customer id']),
            'payment-methods/*/attach' => Http::response(['id' => 'attach customer id']),
        ]);

        $response = $this->tilledPaymentService->createOrUpdateCustomer($this->company, $data);

        $this->assertEquals($response, $customerId);
    }
}
