<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use App\Modules\ERP\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company() . ' Gateway',
            'type' => fake()->randomElement(['stripe', 'paypal', 'bank_transfer']),
            'is_active' => true,
            'is_default' => false,
            'credentials' => Crypt::encryptString(json_encode([
                'secret_key' => 'test_secret_key',
                'publishable_key' => 'test_publishable_key',
            ])),
            'settings' => [],
            'description' => fake()->sentence(),
        ];
    }
}















