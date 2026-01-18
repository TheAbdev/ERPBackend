<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use App\Modules\CRM\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true) . ' Template',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'type' => fake()->randomElement(['lead', 'contact', 'deal', 'invoice', null]),
            'is_active' => true,
            'variables' => ['name', 'email', 'company'],
        ];
    }
}

