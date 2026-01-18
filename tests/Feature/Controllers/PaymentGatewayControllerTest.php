<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\ERP\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentGatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_payment_gateways(): void
    {
        PaymentGateway::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/erp/payment-gateways');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'is_active'],
                ],
            ]);
    }

    public function test_can_create_payment_gateway(): void
    {
        $data = [
            'name' => 'Test Stripe Gateway',
            'type' => 'stripe',
            'credentials' => [
                'secret_key' => 'sk_test_123',
                'publishable_key' => 'pk_test_123',
            ],
            'is_active' => true,
        ];

        $response = $this->postJson('/api/erp/payment-gateways', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'type'],
            ]);

        $this->assertDatabaseHas('payment_gateways', [
            'tenant_id' => $this->tenant->id,
            'name' => $data['name'],
            'type' => $data['type'],
        ]);
    }

    public function test_can_update_payment_gateway(): void
    {
        $gateway = PaymentGateway::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = [
            'name' => 'Updated Gateway Name',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/erp/payment-gateways/{$gateway->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payment_gateways', [
            'id' => $gateway->id,
            'name' => $data['name'],
            'is_active' => false,
        ]);
    }

    public function test_can_delete_payment_gateway(): void
    {
        $gateway = PaymentGateway::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/api/erp/payment-gateways/{$gateway->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('payment_gateways', [
            'id' => $gateway->id,
        ]);
    }
}






