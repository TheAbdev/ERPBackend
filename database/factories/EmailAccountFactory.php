<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use App\Modules\CRM\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class EmailAccountFactory extends Factory
{
    protected $model = EmailAccount::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company() . ' Email',
            'email' => fake()->unique()->safeEmail(),
            'type' => fake()->randomElement(['smtp', 'imap']),
            'credentials' => Crypt::encryptString(json_encode([
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => fake()->email(),
                'password' => 'password123',
            ])),
            'is_active' => true,
            'auto_sync' => false,
            'settings' => [],
        ];
    }
}






