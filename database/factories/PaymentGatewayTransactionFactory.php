<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Models\PaymentGatewayTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayTransactionFactory extends Factory
{
    protected $model = PaymentGatewayTransaction::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'payment_gateway_id' => PaymentGateway::factory(),
            'payment_id' => null,
            'gateway_transaction_id' => 'txn_' . fake()->uuid(),
            'gateway_type' => fake()->randomElement(['stripe', 'paypal', 'bank_transfer']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'refunded']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'payment_method' => fake()->randomElement(['card', 'bank_transfer', 'paypal']),
            'gateway_response' => [],
            'metadata' => [],
            'failure_reason' => null,
            'processed_at' => null,
        ];
    }
}















