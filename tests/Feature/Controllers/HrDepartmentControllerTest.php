<?php

namespace Tests\Feature\Controllers;

use App\Core\Models\Tenant;
use App\Models\User;
use App\Modules\HR\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrDepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_department(): void
    {
        $payload = [
            'name' => 'Human Resources',
            'code' => 'HR',
            'description' => 'HR Department',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/hr/departments', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name']]);

        $this->assertDatabaseHas('hr_departments', [
            'name' => 'Human Resources',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_can_list_departments(): void
    {
        Department::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Operations',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/hr/departments');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }
}

