<?php

namespace Database\Factories;

use App\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();
        $slug = \Illuminate\Support\Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'subdomain' => $slug,
            'domain' => null,
            'status' => 'active',
            'owner_user_id' => null,
            'settings' => [],
        ];
    }
}





